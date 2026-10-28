@if (\Simplify\Plugins\Filament\Admin\Pages\BrowseHub::canAccess())
    <x-filament::button
        tag="a"
        color="gray"
        icon="tabler-world-search"
        :href="\Simplify\Plugins\Filament\Admin\Pages\BrowseHub::getUrl(panel: 'admin')"
    >
        {{ __('simplify-plugins::plugins.title') }}
    </x-filament::button>
@endif
