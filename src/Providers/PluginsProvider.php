<?php

declare(strict_types=1);

namespace Simplify\Plugins\Providers;

use App\Filament\Admin\Resources\Plugins\Pages\ListPlugins;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\ServiceProvider;

final class PluginsProvider extends ServiceProvider
{
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
