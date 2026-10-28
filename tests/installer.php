<?php

// Installing takes the Hub's download and checksum, and refuses anything that is not on the Hub.

declare(strict_types=1);

require __DIR__.'/fixtures/autoload.php';

use Simplify\Plugins\Services\HubInstaller;

$sha = str_repeat('ab', 32);
$hub = 'https://hub.pelican.dev/plugins/nord-theme/download/84/nord-theme.zip';

function refused(array $update): bool
{
    try {
        HubInstaller::release($update, 'nord-theme', '1.0.0-rc.2');

        return false;
    } catch (RuntimeException) {
        return true;
    }
}

$release = HubInstaller::release(['*' => ['version' => '1.0.0', 'download_url' => $hub, 'sha256' => strtoupper($sha)]], 'nord-theme', '1.0.0-rc.2');
check('the Hub download is used', $release === ['version' => '1.0.0', 'url' => $hub, 'sha256' => $sha]);

$release = HubInstaller::release([
    '*' => ['version' => '1.0.0', 'download_url' => $hub, 'sha256' => $sha],
    '1.0.0-rc.2' => ['version' => '1.1.0', 'download_url' => $hub, 'sha256' => $sha],
], 'nord-theme', '1.0.0-rc.2');
check('the entry for this panel version wins', $release['version'] === '1.1.0');

$release = HubInstaller::release(['nord-theme' => ['*' => ['download_url' => $hub, 'sha256' => $sha]]], 'nord-theme', '1.0.0-rc.2');
check('an update.json for several plugins works', $release['url'] === $hub);

check('GitHub is refused', refused(['*' => ['download_url' => 'https://github.com/x/y/releases/download/v1/y.zip', 'sha256' => $sha]]));
check('http is refused', refused(['*' => ['download_url' => 'http://hub.pelican.dev/x.zip', 'sha256' => $sha]]));
check('a missing checksum is refused', refused(['*' => ['download_url' => $hub]]));
check('a short checksum is refused', refused(['*' => ['download_url' => $hub, 'sha256' => 'abc']]));
check('an empty answer is refused', refused([]));

finish('installing from the Hub');
