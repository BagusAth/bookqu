{{--
    Side summary on desktop (design A §6): head in the brand tint, detail rows, total, the primary action and a
    short rule. Sticky when `sticky`. `rows` are prepared label => value pairs; the default slot adds more
    (Alpine-bound rows, steppers). Slots: `action` (primary button), `note` (rule text).
--}}
@props(['title', 'eyebrow' => 'Pilihan Anda', 'rows' => [], 'total' => null, 'totalLabel' => 'Total', 'sticky' => true])

<aside aria-label="{{ $eyebrow }}" {{ $attributes->class(['sf-card-raised overflow-hidden', 'lg:sticky lg:top-6' => $sticky]) }}>
    <div class="bg-sf-tint px-5 py-[18px]">
        <p class="text-xs font-bold text-sf-primary-text">{{ $eyebrow }}</p>
        <p class="mt-1.5 text-[17px] font-bold">{{ $title }}</p>
    </div>
    <div class="px-5 py-[18px]">
        @if ($rows !== [] || $slot->isNotEmpty())
            <dl class="flex flex-col gap-2.5 text-sm">
                @foreach ($rows as $label => $value)
                    <div class="flex justify-between gap-4">
                        <dt class="text-sf-muted">{{ $label }}</dt>
                        <dd class="sf-num text-right font-bold">{{ $value }}</dd>
                    </div>
                @endforeach
                {{ $slot }}
            </dl>
        @endif
        @if ($total !== null)
            <div class="mt-4 flex items-baseline justify-between gap-4 border-t border-sf-divider pt-3.5">
                <span class="text-sm text-sf-muted">{{ $totalLabel }}</span>
                <span class="sf-num text-2xl font-bold">{{ $total }}</span>
            </div>
        @endif
        @isset($action)
            <div class="mt-4">{{ $action }}</div>
        @endisset
        @isset($note)
            <div class="mt-3.5 text-[13px] leading-[19px] text-sf-muted">{{ $note }}</div>
        @endisset
    </div>
</aside>
