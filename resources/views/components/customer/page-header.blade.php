@props(['title', 'description' => null])

<header {{ $attributes->class('space-y-1') }}>
    <h1 class="text-bq-title-phone text-bq-ink sm:text-bq-title">{{ $title }}</h1>
    @if ($description)
        <p class="text-bq-body text-bq-text-muted">{{ $description }}</p>
    @endif
    {{ $slot }}
</header>
