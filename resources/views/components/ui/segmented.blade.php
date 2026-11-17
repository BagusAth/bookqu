@props(['label', 'options', 'selected' => null, 'name' => null])

<div role="{{ $name !== null ? 'radiogroup' : 'group' }}" aria-label="{{ $label }}" {{ $attributes->class('no-scrollbar inline-flex max-w-full gap-0.5 overflow-x-auto rounded-bq-control bg-bq-surface-2 p-[3px] text-bq-small') }}>
    @foreach ($options as $value => $option)
        @php $active = (string) $selected === (string) $value; @endphp
        @if ($name !== null)
            <label class="relative cursor-pointer">
                <input type="radio" name="{{ $name }}" value="{{ $value }}" @checked($active) class="peer sr-only">
                <span class="flex h-[30px] items-center rounded px-3 font-medium whitespace-nowrap text-bq-text-muted peer-checked:bg-bq-surface peer-checked:font-semibold peer-checked:text-bq-ink peer-checked:shadow-[0_1px_2px_rgb(29_22_56/0.12)] peer-focus-visible:outline-2 peer-focus-visible:outline-bq-primary">{{ $option }}</span>
            </label>
        @else
            <a href="{{ $option['href'] }}" @if ($active) aria-current="page" @endif
                @class(['flex h-[30px] items-center rounded px-3 font-medium whitespace-nowrap', 'bg-bq-surface font-semibold text-bq-ink shadow-[0_1px_2px_rgb(29_22_56/0.12)]' => $active, 'text-bq-text-muted hover:text-bq-ink' => ! $active])>{{ $option['label'] }}</a>
        @endif
    @endforeach
</div>
