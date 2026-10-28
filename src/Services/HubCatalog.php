<?php

declare(strict_types=1);

namespace Simplify\Plugins\Services;

use Illuminate\Support\Facades\Cache;
use Simplify\Plugins\Support\HubText;
use Throwable;

/**
 * The Hub's list of free themes, plugins and languages. It is read at most once an hour; a failed read is tried
 * again after five minutes, so a slow or unreachable Hub never slows down every page view.
 */
final class HubCatalog
{
    public const TYPES = ['theme', 'plugin', 'language'];

    private const CACHE = 'simplify-plugins.catalog';

    private const MAX_PAGES = 10;

    /** @return array{items: list<array<string, mixed>>, at: ?int, error: bool} */
    public static function catalog(): array
    {
        $cached = Cache::get(self::CACHE);

        return is_array($cached) ? $cached : self::refresh();
    }

    /** @return array{items: list<array<string, mixed>>, at: ?int, error: bool} */
    public static function refresh(): array
    {
        try {
            $catalog = ['items' => self::build(self::readList()), 'at' => time(), 'error' => false];
            Cache::put(self::CACHE, $catalog, now()->addHour());
        } catch (Throwable $exception) {
            report($exception);
            $catalog = ['items' => [], 'at' => null, 'error' => true];
            Cache::put(self::CACHE, $catalog, now()->addMinutes(5));
        }

        return $catalog;
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE);
    }

    /**
     * One item with its screenshots and license, read when someone opens it.
     *
     * @return array<string, mixed>|null
     */
    public static function details(string $slug): ?array
    {
        if (! self::validSlug($slug)) {
            return null;
        }

        return Cache::remember(self::CACHE.'.'.$slug, now()->addHour(), function () use ($slug): ?array {
            try {
                return self::normalize(HubApi::get('/api/plugins/'.$slug));
            } catch (Throwable $exception) {
                report($exception);

                return null;
            }
        });
    }

    /** @return list<array<mixed>> */
    private static function readList(): array
    {
        $rows = [];
        $page = 1;
        do {
            $body = HubApi::get('/api/plugins?page='.$page);
            foreach ((array) ($body['data'] ?? []) as $row) {
                if (is_array($row)) {
                    $rows[] = $row;
                }
            }
            $last = min(self::MAX_PAGES, (int) ($body['last_page'] ?? 1));
        } while (++$page <= $last);

        // Themes are chosen by how they look, so their screenshots are read up front: one request per theme.
        foreach ($rows as $index => $row) {
            if (($row['type'] ?? '') === 'theme' && self::validSlug((string) ($row['slug'] ?? ''))) {
                try {
                    $rows[$index] = [...$row, ...array_filter(HubApi::get('/api/plugins/'.$row['slug']), fn ($value) => $value !== null)];
                } catch (Throwable) {
                    // The list still shows the theme, just without screenshots.
                }
            }
        }

        return $rows;
    }

    /**
     * Hub rows to view rows: free items with a valid slug, one per slug.
     *
     * @param  list<array<mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    public static function build(array $rows): array
    {
        $items = [];
        foreach ($rows as $row) {
            $item = self::normalize((array) $row);
            if ($item) {
                $items[$item['slug']] ??= $item;
            }
        }

        return array_values($items);
    }

    /** @return array<string, mixed>|null */
    public static function normalize(array $row): ?array
    {
        $slug = (string) ($row['slug'] ?? '');
        $type = (string) ($row['type'] ?? '');
        if (! self::validSlug($slug) || ! in_array($type, self::TYPES, true) || ($row['pricing_type'] ?? 'free') !== 'free') {
            return null;
        }

        $description = (string) ($row['description'] ?? '');
        $screenshots = array_values(array_filter(
            (array) ($row['screenshots'] ?? []),
            fn ($url) => is_string($url) && str_starts_with($url, 'https://') && filter_var($url, FILTER_VALIDATE_URL),
        ));

        return [
            'slug' => $slug,
            'type' => $type,
            'title' => HubText::clip((string) ($row['title'] ?? $slug), 80),
            'author' => HubText::clip((string) ($row['author_name'] ?? ''), 60),
            'summary' => HubText::summary($description),
            'based_on' => HubText::basedOn($description),
            'downloads' => max(0, (int) ($row['downloads_count'] ?? 0)),
            'version' => HubText::clip((string) ($row['latest_version'] ?? ''), 30),
            'updated' => self::date($row['last_version_at'] ?? $row['approved_at'] ?? null),
            'license' => is_string($row['license'] ?? null) ? HubText::clip($row['license'], 30) : null,
            'screenshots' => array_slice($screenshots, 0, 4),
            'featured' => (bool) ($row['is_featured'] ?? false),
            'url' => HubApi::HUB.'/plugins/'.$slug,
        ];
    }

    /**
     * Makers of one type, most items first, then most downloads.
     *
     * @param  list<array<string, mixed>>  $items
     * @return list<array{name: string, count: int, downloads: int}>
     */
    public static function makers(array $items, string $type): array
    {
        $makers = [];
        foreach ($items as $item) {
            if ($item['type'] !== $type || $item['author'] === '') {
                continue;
            }
            $makers[$item['author']] ??= ['name' => $item['author'], 'count' => 0, 'downloads' => 0];
            $makers[$item['author']]['count']++;
            $makers[$item['author']]['downloads'] += $item['downloads'];
        }
        $makers = array_values($makers);
        usort($makers, fn ($a, $b) => [$b['count'], $b['downloads'], $a['name']] <=> [$a['count'], $a['downloads'], $b['name']]);

        return $makers;
    }

    public static function validSlug(string $slug): bool
    {
        return preg_match('/^[a-z0-9][a-z0-9-]{0,79}$/', $slug) === 1;
    }

    private static function date(mixed $value): ?string
    {
        if (! is_string($value) || ($time = strtotime($value)) === false) {
            return null;
        }

        return gmdate('Y-m-d', $time);
    }
}
