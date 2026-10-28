<?php

// Loads the plugin inside a real Pelican panel and opens the admin area. Run from the panel folder after
// `php artisan p:plugin:install simplify-plugins`: php plugins/simplify-plugins/tests/panel/smoke.php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\Concerns\InteractsWithAuthentication;
use Illuminate\Foundation\Testing\Concerns\InteractsWithSession;
use Illuminate\Foundation\Testing\Concerns\MakesHttpRequests;
use Illuminate\Testing\TestResponse;

define('LARAVEL_START', microtime(true));
require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

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
    ?? User::factory()->create(['email' => 'smoke-plugins@example.com', 'root_admin' => true]);

step('the admin area renders with the plugin enabled', function () use ($admin) {
    $response = http($admin, '/admin');
    if ($response->getStatusCode() !== 200) {
        throw new RuntimeException('HTTP '.$response->getStatusCode()
            .($response->exception ? ': '.$response->exception->getMessage() : ''));
    }
});

step('the translations load', function () {
    if (__('simplify-plugins::plugins.title') !== 'Browse Hub') {
        throw new RuntimeException('got '.__('simplify-plugins::plugins.title'));
    }
});

exit($failed ? 1 : 0);
