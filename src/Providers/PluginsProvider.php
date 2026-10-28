<?php

declare(strict_types=1);

namespace Simplify\Plugins\Providers;

use App\Filament\Admin\Resources\Plugins\Pages\ListPlugins;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\ServiceProvider;
use Simplify\Plugins\Addon\PluginsAddon;

final class PluginsProvider extends ServiceProvider
{
    public function register(): void
    {
        // Shows Simplify Plugins inside Simplify when that is installed. Tagging stores only the class name, so
        // without Simplify the class is never loaded.
        $this->app->tag([PluginsAddon::class], 'simplify.addons');
    }

    public function boot(): void
    {
        // A "Browse Hub" button on Pelican's own Plugins page.
        FilamentView::registerRenderHook(
            PanelsRenderHook::PAGE_HEADER_ACTIONS_BEFORE,
            fn () => view('simplify-plugins::plugins-button'),
            scopes: ListPlugins::class,
        );
    }
}
