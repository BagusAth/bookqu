{{-- Quantity stepper with named − and + buttons (44 px) on the customer tokens (design A §7.3). --}}
@props(['label', 'model', 'id', 'min' => 0, 'max' => 9999, 'value' => 0, 'name' => null])

<div {{ $attributes->class('inline-flex shrink-0 items-center gap-1.5') }}>
    <button type="button" class="sf-btn-secondary sf-btn-icon text-xl"
        x-on:click="{{ $model }} = Math.max({{ $min }}, (Number({{ $model }}) || 0) - 1)"
        aria-label="Kurangi {{ $label }}">−</button>
    <input id="{{ $id }}" @if($name) name="{{ $name }}" @endif type="number" inputmode="numeric"
        min="{{ $min }}" max="{{ $max }}" value="{{ $value }}" aria-label="{{ $label }}"
        x-model.number="{{ $model }}" class="sf-num w-10 min-w-0 border-0 bg-transparent p-0 text-center text-[17px] font-bold [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none">
    <button type="button" class="sf-btn sf-btn-icon text-xl"
        x-on:click="{{ $model }} = Math.min({{ $max }}, (Number({{ $model }}) || 0) + 1)"
        aria-label="Tambah {{ $label }}">+</button>
</div>
