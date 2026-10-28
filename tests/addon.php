<?php

// The add-on class fits Simplify's add-on contract (ADDONS.md in Simplify), checked against a copy of that contract.

declare(strict_types=1);

namespace Simplify\Core\Addons {
    abstract class Addon
    {
        abstract public function id(): string;

        abstract public function name(): string;

        public function description(): string
        {
            return '';
        }

        public function icon(): string
        {
            return 'tabler-puzzle';
        }

        public function dashboardActions(): array
        {
            return [];
        }

        public function settingsUrl(): ?string
        {
            return null;
        }
    }

    final readonly class Button
    {
        public function __construct(public string $label, public string $url, public ?string $icon = null, public bool $primary = false) {}
    }
}

namespace {
    use Simplify\Plugins\Addon\PluginsAddon;

    require __DIR__.'/fixtures/autoload.php';

    $addon = new PluginsAddon;
    check('the id is the plugin id', $addon->id() === json_decode(file_get_contents(__DIR__.'/../plugin.json'), true)['id']);
    check('it has a name and a description', $addon->name() === 'Plugins' && $addon->description() === 'simplify-plugins::plugins.addon_description');
    check('the icon is a Tabler icon', str_starts_with($addon->icon(), 'tabler-'));

    finish('Simplify add-on');
}
