@php
    $field = 'mt-1 block w-full rounded-lg border border-bq-border bg-bq-surface px-3 py-2 text-sm';
    $formKey = $unit ? 'unit-edit-'.$unit['id'] : 'unit-create';
    $value = fn ($key, $default = null) => old('_form') === $formKey
        ? old($key, $unit[$key] ?? $default)
        : ($unit[$key] ?? $default);
@endphp
<form method="POST" action="{{ $unit ? route('owner.units.update', $unit['id']) : route('owner.units.store') }}" class="mt-3 space-y-3">
    @csrf
    <input type="hidden" name="_form" value="{{ $formKey }}">
    @if ($unit)
        @method('PUT')
        @foreach ($unit['service_ids'] as $serviceId)
            <input type="hidden" name="service_ids[]" value="{{ $serviceId }}">
        @endforeach
    @endif
    <div class="grid gap-3 sm:grid-cols-2">
        <label class="text-sm">Nama <input name="name" required maxlength="100" value="{{ $value('name') }}" class="{{ $field }}"><x-owner.field-error name="name" :form="$formKey" :id="$formKey.'-name-error'" /></label>
        <label class="text-sm">Jenis <input name="type" required maxlength="100" value="{{ $value('type') }}" class="{{ $field }}"><x-owner.field-error name="type" :form="$formKey" :id="$formKey.'-type-error'" /></label>
        <label class="text-sm">Kapasitas <input name="kapasitas" type="number" min="1" max="9999" required value="{{ $value('kapasitas', 1) }}" class="{{ $field }}"><x-owner.field-error name="kapasitas" :form="$formKey" :id="$formKey.'-kapasitas-error'" /></label>
        <label class="text-sm">Lokasi <input name="lokasi" maxlength="255" value="{{ $value('lokasi') }}" class="{{ $field }}"><x-owner.field-error name="lokasi" :form="$formKey" :id="$formKey.'-lokasi-error'" /></label>
    </div>
    <x-owner.gallery-picker name="image_asset_id" :images="$images" :selected="$value('image_asset_id')" label="Gambar unit" />
    <x-owner.field-error name="image_asset_id" :form="$formKey" />
    <x-owner.field-error name="service_ids.*" :form="$formKey" />
    <label class="flex items-center gap-2 text-sm">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" @checked($value('is_active', true))> Aktif
    </label>
    <x-owner.field-error name="is_active" :form="$formKey" />
    <button type="submit" class="rounded-lg bg-bq-primary px-4 py-2 text-sm font-medium text-white">Simpan unit</button>
</form>
