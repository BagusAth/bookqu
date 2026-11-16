@php
    $field = 'mt-1 block w-full rounded-lg border border-bq-border bg-bq-surface px-3 py-2 text-sm';
@endphp
<form method="POST" action="{{ $unit ? route('owner.units.update', $unit['id']) : route('owner.units.store') }}" class="mt-3 space-y-3">
    @csrf
    @if ($unit)
        @method('PUT')
        @foreach ($unit['service_ids'] as $serviceId)
            <input type="hidden" name="service_ids[]" value="{{ $serviceId }}">
        @endforeach
    @endif
    <div class="grid gap-3 sm:grid-cols-2">
        <label class="text-sm">Nama <input name="name" required maxlength="100" value="{{ $unit['name'] ?? '' }}" class="{{ $field }}"></label>
        <label class="text-sm">Jenis <input name="type" required maxlength="100" value="{{ $unit['type'] ?? '' }}" class="{{ $field }}"></label>
        <label class="text-sm">Kapasitas <input name="kapasitas" type="number" min="1" max="9999" required value="{{ $unit['kapasitas'] ?? 1 }}" class="{{ $field }}"></label>
        <label class="text-sm">Lokasi <input name="lokasi" maxlength="255" value="{{ $unit['lokasi'] ?? '' }}" class="{{ $field }}"></label>
    </div>
    <x-owner.gallery-picker name="image_asset_id" :images="$images" :selected="$unit['image_asset_id'] ?? null" label="Gambar unit" />
    <label class="flex items-center gap-2 text-sm">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" @checked($unit['is_active'] ?? true)> Aktif
    </label>
    <button type="submit" class="rounded-lg bg-bq-primary px-4 py-2 text-sm font-medium text-white">Simpan unit</button>
</form>
