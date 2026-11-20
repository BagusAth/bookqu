@props(['current' => 1, 'step' => null])

@php
    $currentStep = (int) ($step ?: $current);
    $steps = [1 => 'Layanan', 2 => 'Jadwal', 3 => 'Data & Pembayaran'];
@endphp

<nav aria-label="Progres pemesanan">
    <ol class="mx-auto flex max-w-xl flex-wrap justify-between gap-x-2 gap-y-2">
        @foreach ($steps as $number => $label)
            <li @if ($number === $currentStep) aria-current="step" @endif class="flex items-center gap-1.5 text-bq-phone-nav sm:gap-2 sm:text-bq-small">
                <span aria-hidden="true" @class([
                    'grid size-6 shrink-0 place-items-center rounded-full border font-semibold',
                    'border-bq-primary bg-bq-primary text-bq-on-primary' => $number === $currentStep,
                    'border-bq-border-strong bg-bq-surface-2 text-bq-text-muted' => $number !== $currentStep,
                ])>{{ $number }}</span>
                <span @class(['font-semibold' => $number === $currentStep, 'text-bq-text-muted' => $number !== $currentStep])>{{ $label }}</span>
            </li>
        @endforeach
    </ol>
</nav>
