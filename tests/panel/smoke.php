<?php

// Loads the plugin inside a real Pelican panel and opens the admin area. Run from the panel folder after
// `php artisan p:plugin:install simplify-plugins`: php plugins/simplify-plugins/tests/panel/smoke.php

declare(strict_types=1);

use App\Models\Plugin;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\Concerns\InteractsWithAuthentication;
use Illuminate\Foundation\Testing\Concerns\InteractsWithSession;
use Illuminate\Foundation\Testing\Concerns\MakesHttpRequests;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\TestResponse;
use Simplify\Plugins\Filament\Admin\Pages\BrowseHub;
use Simplify\Plugins\Services\HubApi;
use Simplify\Plugins\Services\HubCatalog;
use Simplify\Plugins\Services\HubInstaller;

define('LARAVEL_START', microtime(true));
require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

// Laravel's console handler would print an uncaught error and still exit 0, which CI reads as a pass.
set_exception_handler(function (Throwable $e) {
    echo 'FAIL uncaught '.get_class($e).': '.substr($e->getMessage(), 0, 400).' ('.basename($e->getFile()).':'.$e->getLine().")\n";
    exit(1);
});

$failed = false;

function step(string $what, callable $run): void
{
    global $failed;
    try {
        $run();
        echo "PASS $what\n";
    } catch (Throwable $e) {
        $failed = true;
        echo "FAIL $what: ".get_class($e).': '.substr($e->getMessage(), 0, 400)
            .' ('.basename($e->getFile()).':'.$e->getLine().")\n";
    }
}

function must(bool $ok, string $why): void
{
    if (! $ok) {
        throw new RuntimeException($why);
    }
}

function http(User $user, string $url): TestResponse
{
    $client = new class(app())
    {
        use InteractsWithAuthentication;
        use InteractsWithSession;
        use MakesHttpRequests;

        public function __construct(public $app) {}
    };

    return $client->actingAs($user)->get($url);
}

$admin = User::where('email', 'smoke-plugins@example.com')->first()
    ?? User::factory()->create(['email' => 'smoke-plugins@example.com']);
$admin->syncRoles(Role::getRootAdmin());

step('the admin area renders with the plugin enabled', function () use ($admin) {
    $response = http($admin, '/admin');
    if ($response->getStatusCode() !== 200) {
        throw new RuntimeException('HTTP '.$response->getStatusCode()
            .($response->exception ? ': '.$response->exception->getMessage() : ''));
    }
});

// A fake Hub: one theme with a screenshot and one plugin.
HubApi::$transport = function (string $path): array {
    $theme = ['slug' => 'smoke-theme', 'title' => 'Smoke Theme', 'type' => 'theme', 'author_name' => 'Smoke Maker', 'description' => 'A theme.', 'pricing_type' => 'free'];
    if ($path === '/api/plugins/smoke-theme') {
        return [...$theme, 'screenshots' => ['https://example.com/shot.png'], 'license' => 'MIT'];
    }

    return ['last_page' => 1, 'data' => [$theme, ['slug' => 'smoke-plugin', 'title' => 'Smoke Plugin', 'type' => 'plugin', 'author_name' => 'Other Maker', 'pricing_type' => 'free']]];
};
HubCatalog::forget();
Cache::forget('simplify-plugins.catalog.smoke-theme');

step('Browse Hub renders', function () use ($admin) {
    $url = BrowseHub::getUrl(panel: 'admin');
    $response = http($admin, $url);
    if ($response->getStatusCode() !== 200) {
        throw new RuntimeException('HTTP '.$response->getStatusCode().' at '.$url.($response->exception ? ': '.$response->exception->getMessage() : ''));
    }
});

step('Browse Hub lists the Hub with its makers and details', function () use ($admin) {
    Filament\Facades\Filament::setCurrentPanel(Filament\Facades\Filament::getPanel('admin'));
    // PHPUnit's assertions need a PHPUnit run, so the page's HTML is checked by hand.
    $page = Livewire\Livewire::actingAs($admin)->test(BrowseHub::class)->call('load');
    $html = $page->html();
    must(str_contains($html, 'Smoke Theme') && str_contains($html, 'Made by Smoke Maker'), 'the theme and its maker are missing');
    must(str_contains($html, 'https://example.com/shot.png'), 'the screenshot is missing');
    must(! str_contains($html, 'Smoke Plugin'), 'a plugin shows on the Themes tab');
    must(str_contains($page->set('type', 'plugin')->html(), 'Smoke Plugin'), 'the Plugins tab does not show the plugin');
    must(str_contains($page->set('type', 'theme')->set('search', 'nothing like this')->html(), 'Nothing found'), 'an empty search does not say so');
    $page->set('search', '')->mountAction('view', ['slug' => 'smoke-theme']);
    $details = view('simplify-plugins::details', ['item' => HubCatalog::details('smoke-theme')])->render();
    must(str_contains($details, 'MIT') && str_contains($details, 'https://example.com/shot.png'), 'the details do not show the license and screenshots');
});

step('installing refuses a download that is not on the Hub', function () use ($admin) {
    if (! HubInstaller::supported()) {
        echo "  (this Pelican has no checksum check: Browse Hub only links to the Hub)\n";

        return;
    }
    $previous = HubApi::$transport;
    HubApi::$transport = fn (string $path) => str_ends_with($path, '/update.json')
        ? ['*' => ['version' => '1.0.0', 'download_url' => 'https://github.com/example/smoke/releases/download/v1/smoke.zip', 'sha256' => str_repeat('a', 64)]]
        : $previous($path);
    try {
        Livewire\Livewire::actingAs($admin)->test(BrowseHub::class)
            ->call('load')->set('type', 'plugin')
            ->callAction('install', ['slug' => 'smoke-plugin']);
        $titles = array_column((array) session('filament.notifications', []), 'title');
        must(in_array('Install failed', $titles, true), 'no "Install failed" notification, got: '.json_encode($titles));
    } finally {
        HubApi::$transport = $previous;
    }
    Plugin::refreshRows();
    if (Plugin::query()->whereKey('smoke-plugin')->exists()) {
        throw new RuntimeException('a plugin from outside the Hub was installed');
    }
});

step('Pelican\'s Plugins page has the Browse Hub button', function () use ($admin) {
    $response = http($admin, '/admin/plugins');
    if ($response->getStatusCode() !== 200 || ! str_contains($response->getContent(), '/admin/browse-hub')) {
        throw new RuntimeException('HTTP '.$response->getStatusCode().', button '.(str_contains((string) $response->getContent(), 'browse-hub') ? 'found' : 'missing'));
    }
});

step('the translations load', function () {
    if (__('simplify-plugins::plugins.title') !== 'Browse Hub') {
        throw new RuntimeException('got '.__('simplify-plugins::plugins.title'));
    }
});

echo $failed ? "Some steps failed\n" : "All steps passed\n";
exit($failed ? 1 : 0);
