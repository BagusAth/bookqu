{{-- Avatar (BQ-161): initials of a name on a lilac disc. --}}
@props(['name' => ''])

@php
    $initials = collect(preg_split('/\s+/', trim((string) $name)) ?: [])->filter()->take(2)
        ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
@endphp

<span {{ $attributes->class('inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-bq-primary-light text-sm font-semibold text-bq-primary') }} aria-hidden="true">{{ $initials ?: '?' }}</span>
