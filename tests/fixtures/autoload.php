<?php

// Loads the plugin classes for tests that run without Laravel.

declare(strict_types=1);

spl_autoload_register(function (string $class): void {
    $prefix = 'Simplify\\Plugins\\';
    if (str_starts_with($class, $prefix)) {
        require __DIR__.'/../../src/'.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';
    }
});

if (! function_exists('__')) {
    function __($key = null, $replace = [], $locale = null)
    {
        return $key;
    }
}

$failures = [];

function check(string $what, bool $ok): void
{
    global $failures;
    if (! $ok) {
        $failures[] = $what;
    }
}

function finish(string $name): never
{
    global $failures;
    if ($failures) {
        echo "FAIL $name:\n  ".implode("\n  ", $failures)."\n";
        exit(1);
    }
    echo "PASS $name\n";
    exit(0);
}
