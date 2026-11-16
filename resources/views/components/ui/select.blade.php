{{-- Select (BQ-161). --}}
@props(['name' => null])

<select {{ $attributes->merge(array_filter(['name' => $name, 'aria-invalid' => $name && $errors->has($name) ? 'true' : null]))->class('bq-input w-full pr-8') }}>{{ $slot }}</select>
