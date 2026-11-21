{{--
    Sticky bottom action bar on phone and tablet (design A §5, §6); hidden from 1024 px, where the side summary
    takes over. The page content needs `sf-has-bottom-bar` so the bar never covers it (§8).
    Props `label` and `total` fill the left side; the `summary` slot replaces them (for Alpine-bound text).
    Slots: default = the primary action; `note` = a short rule shown from tablet up.
    Set `always` to keep it on desktop too.
--}}
@props(['label' => null, 'total' => null, 'always' => false, 'ariaLabel' => 'Aksi utama'])

<div role="region" aria-label="{{ $ariaLabel }}" @unless ($always) data-phone-tablet @endunless {{ $attributes->class('sf-bottom-bar no-print') }}>
    <div class="mx-auto flex max-w-[1240px] items-center gap-3 md:px-4">
        @isset($summary)
            <div class="min-w-0 grow">{{ $summary }}</div>
        @elseif ($label || $total)
            <div class="min-w-0 grow">
                @if ($label)
                    <p class="truncate text-[13px] text-sf-muted">{{ $label }}</p>
                @endif
                @if ($total)
                    <p class="sf-num text-xl font-bold">{{ $total }}</p>
                @endif
            </div>
        @endisset
        @isset($note)
            <p class="hidden max-w-[220px] text-[13px] text-sf-muted md:block">{{ $note }}</p>
        @endisset
        {{ $slot }}
    </div>
</div>
