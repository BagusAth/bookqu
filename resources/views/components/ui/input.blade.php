{{-- Text input (BQ-161). Marks itself invalid when the named field has an error. --}}
@props(['name' => null, 'type' => 'text'])

<input type="{{ $type }}" @if ($name) name="{{ $name }}" @endif
    @if ($name && $errors->has($name)) aria-invalid="true" @endif
    {{ $attributes->class('bq-input w-full') }}>
