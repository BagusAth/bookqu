<form method="POST" action="{{ route('owner.settings.profile') }}" enctype="multipart/form-data" id="settings-profile"
    x-data
    @submit="
        const slug = $refs.slug.value.trim();
        if (slug !== @js($tenant->slug) && @js((bool) $tenant->slug) && !$event.submitter?.hasAttribute('data-rename-confirmed')) {
            $event.preventDefault();
            $dispatch('open-dialog', { id: 'settings-rename', trigger: $event.submitter });
        }
    "
    class="space-y-4 bq-panel p-4">
    @csrf
    <h2 class="font-semibold">Profil Bisnis</h2>
    <div class="grid gap-3 sm:grid-cols-2">
        <label class="grid gap-1 text-sm">Nama bisnis
            <input id="input-namabisnis" name="namabisnis" required maxlength="150" value="{{ old('namabisnis', $tenant->namabisnis) }}" class="bq-input w-full">
            <x-owner.field-error name="namabisnis" />
        </label>
        <label class="grid gap-1 text-sm">Alamat booking (slug)
            <input name="slug" x-ref="slug" required maxlength="150" pattern="[a-z0-9]+(-[a-z0-9]+)*"
                value="{{ old('slug', $tenant->slug) }}" class="bq-input w-full">
            <x-owner.field-error name="slug" />
        </label>
        <label class="grid gap-1 text-sm">Link peta
            <input type="url" name="map_url" maxlength="2048" value="{{ old('map_url', $tenant->map_url) }}" class="bq-input w-full">
            <x-owner.field-error name="map_url" />
        </label>
        <label class="grid gap-1 text-sm">Email bisnis
            <input type="email" name="business_email" maxlength="150" value="{{ old('business_email', $tenant->business_email) }}" class="bq-input w-full">
            <x-owner.field-error name="business_email" />
        </label>
        <label class="grid gap-1 text-sm">Jenis bisnis
            <input id="input-jenisbisnis" name="jenisbisnis" required maxlength="150" value="{{ old('jenisbisnis', $tenant->jenisbisnis) }}" class="bq-input w-full">
            <x-owner.field-error name="jenisbisnis" />
        </label>
        <label class="grid gap-1 text-sm">Nomor WhatsApp
            <input type="tel" id="input-nomorhp" name="nomorhp" required maxlength="20" value="{{ old('nomorhp', $tenant->nomorhp) }}" class="bq-input w-full">
            <x-owner.field-error name="nomorhp" />
        </label>
        <label class="grid gap-1 text-sm">Alamat
            <input id="input-alamat" name="alamat" required maxlength="255" value="{{ old('alamat', $tenant->alamat) }}" class="bq-input w-full">
            <x-owner.field-error name="alamat" />
        </label>
        <label class="grid gap-1 text-sm sm:col-span-2">Deskripsi
            <textarea name="deskripsi" maxlength="1000" rows="3" class="bq-input w-full">{{ old('deskripsi', $tenant->deskripsi) }}</textarea>
            <x-owner.field-error name="deskripsi" />
        </label>
    </div>
    <p class="text-sm text-bq-text-muted">Zona waktu: WIB.</p>
    @if ($storefrontUrl)
        <p class="text-sm">Tautan booking: <a class="break-all bq-link" href="{{ $storefrontUrl }}">{{ $storefrontUrl }}</a></p>
        <p class="text-sm text-bq-text-muted">Jika alamat booking berubah, tautan, kode QR, dan poster lama akan berhenti berfungsi.</p>
    @endif
    <button class="bq-btn bq-btn-primary">Simpan profil</button>
</form>
<x-owner.dialog id="settings-rename" title="Ubah alamat booking?" description="Jika alamat booking berubah, tautan lama, kode QR, dan poster lama berhenti berfungsi.">
    <button form="settings-profile" type="submit" data-rename-confirmed class="bq-btn bq-btn-primary">Simpan alamat booking baru</button>
</x-owner.dialog>
@if ($canManageTeam)
    <a href="{{ route('owner.team.index') }}" class="mt-4 inline-block text-sm bq-link">Kelola tim</a>
@endif
