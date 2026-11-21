{{--
    Category filter (design A §6). `chips`: a scroll row with a fade on phone, wrapping from tablet up.
    `nav`: the desktop side navigation. Categories: list of ['key', 'label', 'short' (optional), 'count'].
    With `model` (an Alpine property) the buttons set it and read their pressed state from it; without it the
    server-side `active` key is marked.
--}}
@props(['categories', 'active' => null, 'model' => null, 'variant' => 'chips', 'label' => 'Kategori'])

@if ($variant === 'nav')
    <nav aria-label="{{ $label }}" {{ $attributes }}>
        <p class="px-3 pb-2.5 text-xs font-bold text-sf-muted">{{ $label }}</p>
        @foreach ($categories as $category)
            <button type="button" class="sf-nav-item"
                aria-pressed="{{ $category['key'] === $active ? 'true' : 'false' }}"
                @if ($model) x-on:click="{{ $model }} = @js($category['key'])" x-bind:aria-pressed="({{ $model }} === @js($category['key'])).toString()" @endif>
                <span class="min-w-0 truncate" title="{{ $category['label'] }}">{{ $category['label'] }}</span>
                <span class="sf-num text-[13px] font-medium text-sf-muted">{{ $category['count'] }}</span>
            </button>
        @endforeach
        {{ $slot }}
    </nav>
@else
    <div {{ $attributes->class('sf-fade-end md:after:hidden') }}>
        <div role="group" aria-label="{{ $label }}" class="sf-scroll-row gap-2 px-4 py-2 md:flex-wrap md:overflow-visible md:px-0">
            @foreach ($categories as $category)
                <button type="button" class="sf-chip"
                    aria-pressed="{{ $category['key'] === $active ? 'true' : 'false' }}"
                    @if ($model) x-on:click="{{ $model }} = @js($category['key'])" x-bind:aria-pressed="({{ $model }} === @js($category['key'])).toString()" @endif>
                    {{ $category['short'] ?? $category['label'] }}
                    <span class="sf-chip-count sf-num">{{ $category['count'] }}</span>
                </button>
            @endforeach
        </div>
    </div>
@endif
