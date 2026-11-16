{{-- Textarea (BQ-161). --}}
@props(['name' => null])

<textarea @if ($name) name="{{ $name }}" @endif
    @if ($name && $errors->has($name)) aria-invalid="true" @endif
    {{ $attributes->class('bq-input w-full') }}>{{ $slot }}</textarea>
