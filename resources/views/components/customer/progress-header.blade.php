@props([
    'current' => 1,
    'step' => null,
])

@php
    $currentStep = $step ? intval($step) : intval($current);
    $steps = [
        1 => 'Layanan',
        2 => 'Tanggal',
        3 => 'Waktu',
        4 => 'Data Pemesan',
        5 => 'Pembayaran',
        6 => 'Selesai',
    ];
@endphp

<nav aria-label="Progress Booking" class="w-full">
    {{-- Desktop Stepper --}}
    <ol class="hidden sm:flex items-center justify-between w-full">
        @foreach ($steps as $stepNum => $stepName)
            @php
                $isCompleted = $stepNum < $currentStep;
                $isCurrent = $stepNum === $currentStep;
            @endphp
            <li class="flex items-center {{ !$loop->last ? 'flex-1' : '' }}">
                <div class="flex items-center gap-1.5 md:gap-2.5">
                    @if ($isCompleted)
                        <span class="flex h-6 w-6 md:h-7 md:w-7 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-white shadow-2xs">
                            <svg class="h-3.5 w-3.5 md:h-4 md:w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </span>
                        <span class="text-[11px] md:text-xs font-semibold text-[#0F172A] whitespace-nowrap">{{ $stepName }}</span>
                    @elseif ($isCurrent)
                        <span class="flex h-6 w-6 md:h-7 md:w-7 shrink-0 items-center justify-center rounded-full bg-[#4F46E5] text-white font-bold text-xs ring-4 ring-[#EEF2FF] shadow-xs">
                            {{ $stepNum }}
                        </span>
                        <span class="text-[11px] md:text-xs font-bold text-[#4F46E5] whitespace-nowrap">{{ $stepName }}</span>
                    @else
                        <span class="flex h-6 w-6 md:h-7 md:w-7 shrink-0 items-center justify-center rounded-full border border-[#CBD5E1] bg-[#F8FAFC] text-[#94A3B8] font-medium text-xs">
                            {{ $stepNum }}
                        </span>
                        <span class="text-[11px] md:text-xs font-medium text-[#94A3B8] whitespace-nowrap">{{ $stepName }}</span>
                    @endif
                </div>

                @if (!$loop->last)
                    <div class="booking-step-connector mx-1.5 md:mx-3 h-[2px] flex-1 rounded-full transition-all duration-500 ease-out {{ $stepNum < $currentStep ? 'bg-emerald-500' : 'bg-[#E2E8F0]' }}"></div>
                @endif
            </li>
        @endforeach
    </ol>

    {{-- Mobile Stepper --}}
    <div class="flex sm:hidden items-center justify-between">
        <div class="flex items-center gap-2">
            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-[#4F46E5] text-white font-bold text-xs shadow-2xs">
                {{ $currentStep }}
            </span>
            <span class="text-xs font-bold text-[#0F172A]">
                {{ $steps[$currentStep] ?? 'Booking' }}
            </span>
            <span class="text-[11px] text-[#64748B]">
                (Langkah {{ $currentStep }} dari 6)
            </span>
        </div>
        <div class="flex gap-1">
            @foreach ($steps as $stepNum => $stepName)
                <span class="h-1.5 rounded-full transition-all duration-300 ease-out {{ $stepNum < $currentStep ? 'w-3.5 bg-emerald-500' : ($stepNum === $currentStep ? 'bg-[#4F46E5] w-5' : 'w-3.5 bg-[#E2E8F0]') }}"></span>
            @endforeach
        </div>
    </div>
</nav>
