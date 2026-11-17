@props(['label', 'options', 'selected' => null, 'name' => null])

<div role="{{ $name !== null ? 'radiogroup' : 'group' }}" aria-label="{{ $label }}" {{ $attributes->class('inline-flex max-w-full flex-wrap gap-1 rounded-bq-control bg-bq-surface-2 p-1 text-bq-label') }}>
    @foreach ($options as $value => $option)
        @php $active = (string) $selected === (string) $value; @endphp
        @if ($name !== null)
            <label class="relative cursor-pointer">
                <input type="radio" name="{{ $name }}" value="{{ $value }}" @checked($active) class="peer sr-only">
                <span class="block rounded-bq-control px-3 py-1.5 text-bq-text-muted peer-checked:bg-bq-surface peer-checked:text-bq-primary peer-focus-visible:outline-2 peer-focus-visible:outline-bq-primary">{{ $option }}</span>
            </label>
        @else
            <a href="{{ $option['href'] }}" @if ($active) aria-current="page" @endif
                @class(['rounded-bq-control px-3 py-1.5', 'bg-bq-surface text-bq-primary' => $active, 'text-bq-text-muted hover:bg-bq-background' => ! $active])>{{ $option['label'] }}</a>
        @endif
    @endforeach
</div>
