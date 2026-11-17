@props(['label' => 'Jadwal menurut jam'])

<ol aria-label="{{ $label }}" {{ $attributes->class('divide-y divide-bq-border px-4 py-2') }}>
    {{ $slot }}
</ol>
