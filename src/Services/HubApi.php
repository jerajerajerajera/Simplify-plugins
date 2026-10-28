<?php

declare(strict_types=1);

namespace Simplify\Plugins\Services;

use Closure;
use Illuminate\Support\Facades\Http;

/**
 * Reads the Pelican Hub's public JSON API. This is the only outside service the plugin talks to.
 */
final class HubApi
{
    public const HUB = 'https://hub.pelican.dev';

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
            ->withHeaders(['User-Agent' => 'SimplifyPlugins (+https://github.com/jerajerajerajera/simplify-plugins)'])
            ->get(self::HUB.$path)
            ->throw()
            ->json();
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
