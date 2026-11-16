{{--
    Form field (BQ-161): label, control slot, hint and error. `name` drives the error lookup;
    `form` scopes the error to one form (owner-portal.md §9 dialog forms).
--}}
@props([
    'label',
    'for' => null,
    'name' => null,
    'hint' => null,
    'required' => false,
    'form' => null,
])

<div {{ $attributes->class('grid gap-1.5') }}>
    <label @if ($for) for="{{ $for }}" @endif class="bq-label">
        {{ $label }}@if ($required)<span class="text-bq-danger" aria-hidden="true"> *</span>@endif
    </label>
    {{ $slot }}
    @if ($hint)
        <p class="bq-hint">{{ $hint }}</p>
    @endif
    @if ($name)
        <x-owner.field-error :name="$name" :form="$form" />
    @endif
</div>
