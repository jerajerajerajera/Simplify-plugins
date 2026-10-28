@if (! $item)
    <p>{{ __('simplify-plugins::plugins.unreachable') }}</p>
@else
    <div class="sp-hub-details">
        <p class="sp-hub-by-large">{{ __('simplify-plugins::plugins.made_by', ['name' => $item['author'] ?: '?']) }}</p>
        @if ($item['based_on'])
            <p class="sp-hub-meta">
                {{ __('simplify-plugins::plugins.based_on') }}
                <a href="{{ $item['based_on']['url'] }}" target="_blank" rel="noopener noreferrer">{{ $item['based_on']['name'] }}</a>
            </p>
        @endif
        <p>{{ $item['summary'] }}</p>
        <dl class="sp-hub-facts">
            @if ($item['version'])
                <dt>{{ __('simplify-plugins::plugins.version') }}</dt>
                <dd>{{ $item['version'] }}</dd>
            @endif
            @if ($item['updated'])
                <dt>{{ __('simplify-plugins::plugins.updated') }}</dt>
                <dd>{{ $item['updated'] }}</dd>
            @endif
            <dt>{{ __('simplify-plugins::plugins.license') }}</dt>
            <dd>{{ $item['license'] ?: __('simplify-plugins::plugins.license_unknown') }}</dd>
        </dl>
        @foreach ($item['screenshots'] as $shot)
            <img class="sp-hub-detail-shot" src="{{ $shot }}" alt="" loading="lazy">
        @endforeach
    </div>
@endif
