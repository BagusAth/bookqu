{{-- Search field with icon (BQ-162, prototype .cari). Pass native input attributes (name, value, form). --}}
@props(['label' => 'Cari'])

<label class="bq-search">
    <x-owner.icon name="search" />
    <input type="search" placeholder="{{ $label }}" aria-label="{{ $label }}" {{ $attributes->class('bq-input') }}>
</label>
