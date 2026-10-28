<?php

declare(strict_types=1);

namespace Simplify\Plugins\Services;

use App\Jobs\Plugin\InstallPlugin;
use App\Models\User;
use App\Services\Helpers\PluginService;
use Illuminate\Support\Facades\Process;
use ReflectionMethod;
use RuntimeException;
use Throwable;

/**
 * Installs a plugin from the Pelican Hub, and only from there. The Hub's update.json names the download and its
 * sha256; Pelican's own PluginService downloads it, checks the checksum and unpacks it, and Pelican's own install job
 * installs it in the background.
 */
final class HubInstaller
{
    /** Pelican 1.0.0-rc.2 and later check a download against the Hub's checksum; older panels only link to the Hub. */
    public static function supported(): bool
    {
        return (new ReflectionMethod(PluginService::class, 'downloadPluginFromUrl'))->getNumberOfParameters() >= 3;
    }

    /**
     * The download for this panel from a Hub update.json: the entry for this exact panel version, or else the one for
     * every version. Anything not on the Hub or without a valid checksum is refused.
     *
     * @return array{version: string, url: string, sha256: string}
     */
    public static function release(array $update, string $slug, string $panelVersion): array
    {
        $update = is_array($update[$slug] ?? null) ? $update[$slug] : $update;
        $entry = $update[$panelVersion] ?? $update['*'] ?? null;
        $url = is_array($entry) ? (string) ($entry['download_url'] ?? '') : '';
        $sha256 = is_array($entry) ? strtolower((string) ($entry['sha256'] ?? '')) : '';

        if ($url === '' || ! HubApi::onHub($url)) {
            throw new RuntimeException(__('simplify-plugins::plugins.install.not_on_hub'));
        }
        if (preg_match('/^[0-9a-f]{64}$/', $sha256) !== 1) {
            throw new RuntimeException(__('simplify-plugins::plugins.install.no_checksum'));
        }

        return ['version' => (string) ($entry['version'] ?? ''), 'url' => $url, 'sha256' => $sha256];
    }

    /** Downloads the plugin from the Hub and starts Pelican's install job. Returns the plugin id. */
    public static function install(string $slug, User $user): string
    {
        if (! HubCatalog::validSlug($slug) || ! self::supported()) {
            throw new RuntimeException(__('simplify-plugins::plugins.install.not_on_hub'));
        }

        $release = self::release(HubApi::get('/plugins/'.$slug.'/update.json'), $slug, (string) config('app.version'));
        $id = app(PluginService::class)->downloadPluginFromUrl($release['url'], null, $release['sha256']);
        InstallPlugin::dispatch($user, $id);

        return $id;
    }

    /** Themes are built with yarn while they are installed, so a missing yarn is shown before anything starts. */
    public static function canBuildThemes(): bool
    {
        try {
            return Process::path(base_path())->timeout(10)->run(['yarn', '--version'])->successful();
        } catch (Throwable) {
            return false;
        }
    }
}
