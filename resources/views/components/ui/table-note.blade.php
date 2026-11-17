<div {{ $attributes->class('flex flex-wrap items-center justify-between gap-2 border-b border-bq-border px-4 py-3 text-bq-small text-bq-text-muted') }}>
    {{ $slot }}
    @isset($action)
        <span>{{ $action }}</span>
    @endisset
</div>
