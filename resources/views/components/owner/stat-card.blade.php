{{-- Stat Card Component --}}
@props([
    'ikon' => 'default',
    'label' => '',
    'nilai' => '0',
    'perubahan' => 0,
    'tipeperubahan' => 'naik',
])

@php
    $warnaikon = match($ikon) {
        'booking' => 'bg-indigo-50 text-indigo-600',
        'revenue' => 'bg-orange-50 text-orange-500',
        'program' => 'bg-sky-50 text-sky-600',
        default => 'bg-gray-50 text-gray-600',
    };

    $warnaperubahan = match($tipeperubahan) {
        'naik' => 'text-emerald-600',
        'turun' => 'text-rose-500',
        default => 'text-bq-text-subtle',
    };

    $labelperubahan = match($tipeperubahan) {
        'naik' => '+' . $perubahan . '%',
        'turun' => $perubahan . '%',
        default => 'Steady',
    };
@endphp

<div class="group rounded-xl border border-bq-border bg-bq-surface p-5 transition-all duration-300 hover:border-bq-border-strong hover:shadow-md">
    <div class="flex items-start justify-between">
        <div class="flex h-10 w-10 items-center justify-center rounded-lg {{ $warnaikon }} transition-transform duration-300 group-hover:scale-110">
            @switch($ikon)
                @case('booking')
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    @break
                @case('revenue')
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    @break
                @case('program')
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                    </svg>
                    @break
            @endswitch
        </div>

        <span class="text-xs font-semibold {{ $warnaperubahan }}">
            {{ $labelperubahan }}
        </span>
    </div>

    <div class="mt-4">
        <p class="text-xs font-medium tracking-wide text-bq-text-muted uppercase">{{ $label }}</p>
        <p class="mt-1 text-2xl font-bold text-bq-text">{{ $nilai }}</p>
    </div>
</div>
