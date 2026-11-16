{{-- Text input (BQ-161). Marks itself invalid when the named field has an error. --}}
@props(['name' => null, 'type' => 'text'])

<input {{ $attributes->merge(array_filter(['type' => $type, 'name' => $name, 'aria-invalid' => $name && $errors->has($name) ? 'true' : null]))->class('bq-input w-full') }}>
