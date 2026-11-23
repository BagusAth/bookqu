{{--
    Text field of the early access form (BQ-175): label, input, server error and the visitor's old input.
    Extra attributes (autocomplete, inputmode, maxlength, pattern) go to the <input>.
--}}
@props([
    'name',
    'label',
    'type' => 'text',
    'placeholder' => null,
    'hint' => null,
])

@php
    $inputId = 'early-access-'.str_replace('_', '-', $name);
    $error = $errors->first($name);
    $describedBy = trim(($hint ? $inputId.'-hint ' : '').($error ? $inputId.'-error' : ''));
@endphp

<div class="grid gap-1.5">
    <label for="{{ $inputId }}" class="text-sm font-semibold text-bq-ink">
        {{ $label }}<span class="text-bq-danger" aria-hidden="true">*</span>
    </label>
    <input
        id="{{ $inputId }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ old($name) }}"
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        required
        aria-required="true"
        @if ($error) aria-invalid="true" @endif
        @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->class([
            'w-full rounded-xl border bg-white px-4 py-3 text-base text-bq-ink placeholder:text-bq-text-muted/70 focus:outline-none focus:ring-2 focus:ring-bq-primary/30',
            'border-bq-danger' => $error,
            'border-bq-primary/40 focus:border-bq-primary' => ! $error,
        ]) }}
    >
    @if ($hint)
        <p id="{{ $inputId }}-hint" class="text-sm text-bq-text-muted">{{ $hint }}</p>
    @endif
    @if ($error)
        <p id="{{ $inputId }}-error" class="text-sm font-medium text-bq-danger">{{ $error }}</p>
    @endif
</div>
