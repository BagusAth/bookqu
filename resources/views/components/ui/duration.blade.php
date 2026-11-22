@props(['minutes'])

<span {{ $attributes->class('whitespace-nowrap text-bq-small') }}>
    @if ($minutes < 60)
        {{ $minutes }} menit
    @else
        {{ str_replace('.', ',', (string) round($minutes / 60, 2)) }} jam
    @endif
</span>
