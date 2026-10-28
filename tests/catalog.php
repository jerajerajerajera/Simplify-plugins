<?php

// The Hub list: rows are cleaned up, makers are counted and credited, and only Hub addresses count as the Hub.

declare(strict_types=1);

require __DIR__.'/fixtures/autoload.php';

use Simplify\Plugins\Services\HubApi;
use Simplify\Plugins\Services\HubCatalog;
use Simplify\Plugins\Support\HubText;

$nord = [
    'slug' => 'nord-theme', 'title' => 'Nord Theme', 'type' => 'theme', 'author_name' => 'boy132', 'pricing_type' => 'free',
    'description' => "A light and dark arctic Nord theme.\n\n_Originally created by [Andréia Bohner](https://github.com/andreia) as [Filament Theme](https://github.com/andreia/filament-nord-theme), under MIT License._",
    'downloads_count' => 89, 'latest_version' => '1.0.0', 'last_version_at' => '2026-05-29T08:32:43+00:00', 'license' => null,
    'screenshots' => ['https://peli.nyc3.digitaloceanspaces.com/a.png', 'http://insecure.example/b.png', 'javascript:alert(1)'],
];
$item = HubCatalog::normalize($nord);
check('a theme is kept', $item !== null);
check('the summary leaves out the credit line', $item['summary'] === 'A light and dark arctic Nord theme.');
check('the original maker is credited', $item['based_on'] === ['name' => 'Andréia Bohner', 'url' => 'https://github.com/andreia']);
check('only https screenshots are kept', $item['screenshots'] === ['https://peli.nyc3.digitaloceanspaces.com/a.png']);
check('the date is a day', $item['updated'] === '2026-05-29');
check('the page is on the Hub', $item['url'] === 'https://hub.pelican.dev/plugins/nord-theme');

check('paid items are left out', HubCatalog::normalize([...$nord, 'pricing_type' => 'paid']) === null);
check('unknown types are left out', HubCatalog::normalize([...$nord, 'type' => 'egg']) === null);
check('odd slugs are left out', HubCatalog::normalize([...$nord, 'slug' => '../etc']) === null);
check('html in a title is removed', HubCatalog::normalize([...$nord, 'title' => '<b>Nord</b>'])['title'] === 'Nord');

$rows = [
    $nord,
    [...$nord, 'title' => 'Duplicate'],
    ['slug' => 'sakura', 'type' => 'theme', 'author_name' => 'boy132', 'downloads_count' => 10],
    ['slug' => 'aurora', 'type' => 'theme', 'author_name' => 'ankit', 'downloads_count' => 500],
    ['slug' => 'tickets', 'type' => 'plugin', 'author_name' => 'ankit', 'downloads_count' => 5],
];
$items = HubCatalog::build($rows);
check('one item per slug', count($items) === 4 && $items[0]['title'] === 'Nord Theme');
$makers = HubCatalog::makers($items, 'theme');
check('makers with the most items come first', array_column($makers, 'name') === ['boy132', 'ankit']);
check('downloads add up per maker', $makers[0]['downloads'] === 99);

// Every page is read, and nothing past the last one.
$asked = [];
HubApi::$transport = function (string $path) use (&$asked): array {
    $asked[] = $path;
    if ($path === '/api/plugins/t2') {
        return ['screenshots' => ['https://example.com/t2.png'], 'license' => null];
    }
    $page = (int) substr($path, strrpos($path, '=') + 1);

    return ['last_page' => 2, 'data' => [['slug' => 'p'.$page, 'type' => 'plugin'], ['slug' => 't'.$page, 'type' => $page === 2 ? 'theme' : 'plugin']]];
};
$read = (new ReflectionMethod(HubCatalog::class, 'readList'))->invoke(null);
check('all pages are read, plus each theme', $asked === ['/api/plugins?page=1', '/api/plugins?page=2', '/api/plugins/t2']);
check('a theme gets its screenshots', count($read) === 4 && $read[3]['screenshots'] === ['https://example.com/t2.png']);

check('long text is cut at a sentence', HubText::summary(str_repeat('Word ', 20).'end. '.str_repeat('More words here ', 20)) === str_repeat('Word ', 19).'Word end.');
check('markdown is plain text', HubText::summary("# Title\n![img](x.png)\n- **Bold** [link](https://x)") === 'Bold link');

check('the Hub is the Hub', HubApi::onHub('https://hub.pelican.dev/plugins/nord-theme/download/84/nord-theme.zip'));
check('http is not the Hub', ! HubApi::onHub('http://hub.pelican.dev/x.zip'));
check('a look-alike host is not the Hub', ! HubApi::onHub('https://hub.pelican.dev.evil.example/x.zip'));
check('GitHub is not the Hub', ! HubApi::onHub('https://github.com/x/y/releases/download/v1/x.zip'));
check('a login in the address is not the Hub', ! HubApi::onHub('https://evil@hub.pelican.dev/x.zip'));
check('another port is not the Hub', ! HubApi::onHub('https://hub.pelican.dev:8443/x.zip'));

finish('Hub list');
