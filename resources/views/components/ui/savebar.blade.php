{{-- Save bar (BQ-162, prototype ui.simpanBilah): sticks to the bottom of its panel while the form scrolls. --}}
@props(['note' => null])

<div {{ $attributes->class('bq-savebar') }}>
    @if ($note)<span class="mr-auto text-bq-small text-bq-text-muted">{{ $note }}</span>@endif
    {{ $slot }}
</div>
