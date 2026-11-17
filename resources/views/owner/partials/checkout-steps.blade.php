<nav class="bq-panel px-4 py-3" aria-label="Tahapan checkout">
    <ol class="grid grid-cols-3 gap-2">
        @foreach (['Pesanan', 'Pembayaran', 'Invoice'] as $label)
            <li class="flex flex-col items-center gap-1 text-center text-bq-small sm:flex-row sm:justify-center sm:text-left"
                @if ($loop->iteration === $step) aria-current="step" @endif>
                <span aria-hidden="true" @class(['bq-num grid size-7 shrink-0 place-items-center rounded-full font-semibold',
                    'bg-bq-primary text-white' => $loop->iteration === $step,
                    'bg-bq-surface-2 text-bq-text-muted' => $loop->iteration > $step,
                    'bg-bq-success-bg text-bq-success' => $loop->iteration < $step])>
                    @if ($loop->iteration < $step)<x-owner.icon name="check" class="size-4" />@else{{ $loop->iteration }}@endif
                </span>
                <span @class(['font-semibold text-bq-primary' => $loop->iteration === $step, 'text-bq-text-muted' => $loop->iteration !== $step])>{{ $label }}</span>
            </li>
        @endforeach
    </ol>
</nav>
