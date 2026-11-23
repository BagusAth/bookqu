@props(['items' => []])

<dl {{ $attributes->class('grid grid-cols-[minmax(0,2fr)_minmax(0,3fr)] gap-x-4 gap-y-2 text-bq-body') }}>
    @foreach ($items as $label => $value)
        <dt class="break-words text-bq-text-muted">{{ $label }}</dt>
        <dd class="min-w-0 break-words">{{ $value }}</dd>
    @endforeach
    {{ $slot }}
</dl>
