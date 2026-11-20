@props(['label', 'model', 'id', 'min' => 0, 'max' => 9999, 'value' => 0, 'name' => null])

<div {{ $attributes->class('inline-flex items-center gap-1') }}>
    <x-ui.button x-on:click="{{ $model }} = Math.max({{ $min }}, (Number({{ $model }}) || 0) - 1)"
        :aria-label="'Kurangi '.$label" class="min-h-11 min-w-11">−</x-ui.button>
    <input id="{{ $id }}" @if($name) name="{{ $name }}" @endif type="number" inputmode="numeric"
        min="{{ $min }}" max="{{ $max }}" value="{{ $value }}" aria-label="{{ $label }}"
        x-model.number="{{ $model }}" class="bq-input bq-num w-16 min-w-0 px-2 text-center">
    <x-ui.button x-on:click="{{ $model }} = Math.min({{ $max }}, (Number({{ $model }}) || 0) + 1)"
        :aria-label="'Tambah '.$label" class="min-h-11 min-w-11">+</x-ui.button>
</div>
