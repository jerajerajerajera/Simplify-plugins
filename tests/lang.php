<?php

// Every language has exactly the English keys, and keeps every :placeholder of the English text.
declare(strict_types=1);
function flat(array $a, string $p = ''): array
{
    $o = [];
    foreach ($a as $k => $v) {
        if (is_array($v)) {
            $o += flat($v, $p.$k.'.');
        } else {
            $o[$p.$k] = $v;
        }
    }

return $o;
}
$en = flat(require __DIR__.'/../lang/en/plugins.php');
$failures = [];
$dirs = glob(__DIR__.'/../lang/*', GLOB_ONLYDIR);
foreach ($dirs as $dir) {
    $locale = basename($dir);
    $file = $dir.'/plugins.php';
    if (! is_file($file)) {
        $failures[] = "$locale: plugins.php is missing";

        continue;
    }
    $lines = flat(require $file);
    foreach (array_diff_key($en, $lines) as $key => $_) {
        $failures[] = "$locale: missing $key";
    }
    foreach (array_diff_key($lines, $en) as $key => $_) {
        $failures[] = "$locale: unknown key $key";
    }
    foreach (array_intersect_key($lines, $en) as $key => $text) {
        preg_match_all('/:[a-z_]+/', $en[$key], $want);
        preg_match_all('/:[a-z_]+/', (string) $text, $have);
        if (array_diff($want[0], $have[0])) {
            $failures[] = "$locale: $key lost ".implode(', ', array_diff($want[0], $have[0]));
        }
    }
}
if ($failures) {
    echo "FAIL translations:\n  ".implode("\n  ", $failures)."\n";
    exit(1);
}
echo 'PASS '.count($dirs).' languages have every key and placeholder'."\n";
