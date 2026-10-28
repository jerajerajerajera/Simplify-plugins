<article class="sp-hub-card" wire:key="hub-{{ $item['slug'] }}">
    @if ($item['screenshots'])
        <img class="sp-hub-shot" src="{{ $item['screenshots'][0] }}" alt="" loading="lazy">
    @endif
    <div class="sp-hub-body">
        <div class="sp-hub-head">
            <h3 class="sp-hub-title">{{ $item['title'] }}</h3>
            @if ($item['installed'])
                <x-filament::badge color="success" size="sm">{{ __('simplify-plugins::plugins.installed') }}</x-filament::badge>
            @endif
        </div>
        <button type="button" class="sp-hub-by" wire:click="$set('maker', @js($item['author']))">
            {{ __('simplify-plugins::plugins.made_by', ['name' => $item['author'] ?: '?']) }}
        </button>
        @if ($item['based_on'])
            <span class="sp-hub-meta">
                {{ __('simplify-plugins::plugins.based_on') }}
                <a href="{{ $item['based_on']['url'] }}" target="_blank" rel="noopener noreferrer">{{ $item['based_on']['name'] }}</a>
            </span>
        @endif
        <p class="sp-hub-summary">{{ $item['summary'] }}</p>
        <span class="sp-hub-meta">
            @if ($item['version'])
                v{{ $item['version'] }} ·
            @endif
            {{ __('simplify-plugins::plugins.downloads', ['count' => number_format($item['downloads'])]) }}
        </span>
        <div class="sp-hub-actions">
            @if (! $item['installed'] && $this->canInstall())
                {{ ($this->installAction)(['slug' => $item['slug']]) }}
            @endif
            {{ ($this->viewAction)(['slug' => $item['slug']]) }}
            @if ($item['installed'] || ! $this->canInstall())
                <x-filament::button
                    tag="a"
                    size="sm"
                    color="gray"
                    icon="tabler-external-link"
                    :href="$item['url']"
                    target="_blank"
                    rel="noopener"
                >
                    {{ __('simplify-plugins::plugins.view_on_hub') }}
                </x-filament::button>
            @endif
        </div>
    </div>
</article>
