<?php

declare(strict_types=1);

namespace Simplify\Plugins\Services;

use Closure;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Reads the Pelican Hub's public JSON API. This is the only outside service the plugin talks to.
 */
final class HubApi
{
    public const HUB = 'https://hub.pelican.dev';

    private const AGENT = 'SimplifyPlugins (+https://github.com/jerajerajerajera/simplify-plugins)';

    /** Tests replace this with a fake: fn (string $path): array. */
    public static ?Closure $transport = null;

    /** @return array<mixed> */
    public static function get(string $path): array
    {
        if (self::$transport) {
            return (self::$transport)($path);
        }

        return (array) Http::timeout(10)
            ->connectTimeout(4)
            ->acceptJson()
            ->withHeaders(['User-Agent' => self::AGENT])
            ->get(self::HUB.$path)
            ->throw()
            ->json();
    }

    /**
     * Several paths at once, so a list of themes costs one round trip instead of one per theme. A path that fails is
     * left out.
     *
     * @param  list<string>  $paths
     * @return array<string, array<mixed>> path => answer
     */
    public static function many(array $paths): array
    {
        if (self::$transport) {
            $answers = [];
            foreach ($paths as $path) {
                try {
                    $answers[$path] = (self::$transport)($path);
                } catch (Throwable) {
                    // Left out, like a failed request below.
                }
            }

            return $answers;
        }
        $responses = Http::pool(fn (Pool $pool) => array_map(fn (string $path) => $pool->as($path)
            ->timeout(10)
            ->connectTimeout(4)
            ->acceptJson()
            ->withHeaders(['User-Agent' => self::AGENT])
            ->get(self::HUB.$path), $paths), concurrency: 8);
        $answers = [];
        foreach ($responses as $path => $response) {
            if ($response instanceof Response && $response->successful()) {
                $answers[$path] = (array) $response->json();
            }
        }

        return $answers;
    }

    /** True for an https address on the Hub itself, the only place plugins are installed from. */
    public static function onHub(string $url): bool
    {
        $parts = parse_url($url);

        return is_array($parts)
            && ($parts['scheme'] ?? '') === 'https'
            && strtolower($parts['host'] ?? '') === 'hub.pelican.dev'
            && ! isset($parts['user'])
            && ! isset($parts['pass'])
            && ! isset($parts['port']);
    }
}
