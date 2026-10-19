@props([
    'current' => 1,
    'step' => null,
])

@php
    $currentStep = $step ? intval($step) : intval($current);
    // Simplified 3-step booking flow: Program -> Schedule -> Confirm
    $steps = [
        1 => 'Program',
        2 => 'Schedule',
        3 => 'Confirm',
    ];
@endphp

<nav aria-label="Progress Booking" class="w-full">
    {{-- Centered 3-Step Stepper (Desktop & Mobile) --}}
    <ol class="flex items-center justify-center max-w-2xl mx-auto w-full px-2 sm:px-4">
        @foreach ($steps as $stepNum => $stepName)
            @php
                $isCompleted = $stepNum < $currentStep;
                $isCurrent = $stepNum === $currentStep;
            @endphp
            <li class="flex items-center {{ !$loop->last ? 'flex-1' : '' }}">
                <div class="flex items-center gap-2 sm:gap-2.5 shrink-0">
                    @if ($isCompleted)
                        {{-- Completed Step: Solid Blue/Indigo with White Checkmark --}}
                        <span class="flex h-7 w-7 sm:h-8 sm:w-8 shrink-0 items-center justify-center rounded-full bg-[#4F46E5] text-white shadow-2xs transition-transform duration-200">
                            <svg class="h-3.5 w-3.5 sm:h-4 sm:w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </span>
                        <span class="text-xs sm:text-sm font-semibold text-[#0F172A] whitespace-nowrap">{{ $stepName }}</span>
                    @elseif ($isCurrent)
                        {{-- Current Step: Solid Blue/Indigo with Step Number --}}
                        <span class="flex h-7 w-7 sm:h-8 sm:w-8 shrink-0 items-center justify-center rounded-full bg-[#4F46E5] text-white font-bold text-xs sm:text-sm shadow-xs ring-4 ring-[#EEF2FF]">
                            {{ $stepNum }}
                        </span>
                        <span class="text-xs sm:text-sm font-bold text-[#4F46E5] whitespace-nowrap">{{ $stepName }}</span>
                    @else
                        {{-- Inactive Upcoming Step: Subtle Gray Circle with Number --}}
                        <span class="flex h-7 w-7 sm:h-8 sm:w-8 shrink-0 items-center justify-center rounded-full border border-[#CBD5E1] bg-[#F8FAFC] text-[#94A3B8] font-medium text-xs sm:text-sm">
                            {{ $stepNum }}
                        </span>
                        <span class="text-xs sm:text-sm font-medium text-[#94A3B8] whitespace-nowrap">{{ $stepName }}</span>
                    @endif
                </div>

                @if (!$loop->last)
                    {{-- Connecting Line between Steps --}}
                    <div class="mx-2 sm:mx-4 h-[2px] flex-1 rounded-full transition-all duration-500 ease-out {{ $stepNum < $currentStep ? 'bg-[#4F46E5]' : 'bg-[#E2E8F0]' }}"></div>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
