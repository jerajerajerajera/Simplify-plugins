<x-filament-panels::page>
    @include('simplify-plugins::styles')

    <div wire:init="load">
        @if (! $loaded)
            <div class="sp-hub-empty">
                <x-filament::loading-indicator class="sp-hub-spinner" />
                {{ __('simplify-plugins::plugins.loading') }}
            </div>
        @else
            @php($hub = $this->hub())

            <x-filament::tabs>
                @foreach (['theme' => 'tabler-palette', 'plugin' => 'tabler-puzzle', 'language' => 'tabler-language'] as $key => $icon)
                    <x-filament::tabs.item
                        :active="$type === $key"
                        :icon="$icon"
                        :badge="$hub['counts'][$key]"
                        wire:click="$set('type', '{{ $key }}')"
                    >
                        {{ __('simplify-plugins::plugins.types.'.$key) }}
                    </x-filament::tabs.item>
                @endforeach
            </x-filament::tabs>

            <div class="sp-hub-toolbar">
                <x-filament::input.wrapper prefix-icon="tabler-search" class="sp-hub-search">
                    <x-filament::input
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        :placeholder="__('simplify-plugins::plugins.search')"
                    />
                </x-filament::input.wrapper>
                <x-filament::input.wrapper prefix-icon="tabler-user" class="sp-hub-maker">
                    <x-filament::input.select wire:model.live="maker">
                        <option value="">{{ __('simplify-plugins::plugins.all_makers', ['count' => count($hub['makers'])]) }}</option>
                        @foreach ($hub['makers'] as $entry)
                            <option value="{{ $entry['name'] }}">{{ $entry['name'] }} ({{ $entry['count'] }})</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>

            @if ($hub['error'])
                <div class="sp-hub-empty">{{ __('simplify-plugins::plugins.unreachable') }}</div>
            @elseif ($hub['items'] === [])
                <div class="sp-hub-empty">{{ __('simplify-plugins::plugins.nothing_found') }}</div>
            @else
                <div class="sp-hub-grid">
                    @foreach ($hub['items'] as $item)
                        @include('simplify-plugins::card', ['item' => $item])
                    @endforeach
                </div>
            @endif

            <p class="sp-hub-note">{{ __('simplify-plugins::plugins.note') }}</p>
        @endif
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
