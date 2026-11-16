@props(['name', 'form' => null, 'id' => null])

@if ($form === null || old('_form') === $form)
    @error($name)
        <p @if ($id) id="{{ $id }}" @endif {{ $attributes->class(['mt-1 text-xs text-bq-danger']) }}>{{ $message }}</p>
    @enderror
@endif
