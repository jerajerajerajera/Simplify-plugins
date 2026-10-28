<?php

declare(strict_types=1);

namespace Simplify\Plugins\Addon;

use Simplify\Core\Addons\Addon;
use Simplify\Core\Addons\Button;
use Simplify\Plugins\Filament\Admin\Pages\BrowseHub;

/**
 * How Simplify Plugins shows up inside Simplify, when Simplify is installed. Only loaded by Simplify itself.
 */
final class PluginsAddon extends Addon
{
    public function id(): string
    {
        return 'simplify-plugins';
    }

    public function name(): string
    {
        return 'Plugins';
    }

    public function description(): string
    {
        return __('simplify-plugins::plugins.addon_description');
    }

    public function icon(): string
    {
        return 'tabler-world-search';
    }

    public function dashboardActions(): array
    {
        if (! user()?->isRootAdmin() || ! BrowseHub::canAccess()) {
            return [];
        }

        return [new Button(__('simplify-plugins::plugins.title'), BrowseHub::getUrl(panel: 'admin'), 'tabler-world-search')];
    }
}
