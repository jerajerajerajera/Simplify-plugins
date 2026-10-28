<?php

declare(strict_types=1);

namespace Simplify\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;

final class SimplifyPluginsPlugin implements Plugin
{
    public function getId(): string
    {
        return 'simplify-plugins';
    }

    public function register(Panel $panel): void
    {
        if ($panel->getId() === 'admin') {
            $panel->pages([Filament\Admin\Pages\BrowseHub::class]);
        }
    }

    public function boot(Panel $panel): void {}
}
