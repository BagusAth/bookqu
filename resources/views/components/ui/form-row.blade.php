{{-- Form row (BQ-162, prototype ui.formBagian): section title on the left, fields on the right; stacked on phones. --}}
@props(['title', 'description' => null])

<section {{ $attributes->class('bq-form-row') }}>
    <div class="bq-form-row-title">
        <h3>{{ $title }}</h3>
        @if ($description)<p>{{ $description }}</p>@endif
    </div>
    <div class="bq-form-fields">{{ $slot }}</div>
</section>
