{{-- Textarea (BQ-161). --}}
@props(['name' => null])

<textarea {{ $attributes->merge(array_filter(['name' => $name, 'aria-invalid' => $name && isset($errors) && $errors->has($name) ? 'true' : null]))->class('bq-input w-full') }}>{{ $slot }}</textarea>
