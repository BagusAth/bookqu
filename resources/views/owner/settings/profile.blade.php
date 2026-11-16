<form method="POST" action="{{ route('owner.settings.profile') }}" enctype="multipart/form-data" id="settings-profile"
    x-data
    @submit="
        const name = $refs.name.value.trim();
        if (name !== @js($tenant->namabisnis) && @js((bool) $tenant->slug) && !$event.submitter?.hasAttribute('data-rename-confirmed')) {
            $event.preventDefault();
            $dispatch('open-dialog', { id: 'settings-rename', trigger: $event.submitter });
        }
    "
    class="space-y-4 rounded-xl border border-bq-border bg-bq-surface p-4">
    @csrf
    <h2 class="font-semibold">Profil Bisnis</h2>
    <div class="grid gap-3 sm:grid-cols-2">
        <label class="grid gap-1 text-sm">Nama bisnis
            <input id="input-namabisnis" name="namabisnis" x-ref="name" required maxlength="150" value="{{ old('namabisnis', $tenant->namabisnis) }}" class="w-full rounded-lg border border-bq-border bg-bq-surface p-2">
        </label>
        <label class="grid gap-1 text-sm">Jenis bisnis
            <input id="input-jenisbisnis" name="jenisbisnis" required maxlength="150" value="{{ old('jenisbisnis', $tenant->jenisbisnis) }}" class="w-full rounded-lg border border-bq-border bg-bq-surface p-2">
        </label>
        <label class="grid gap-1 text-sm">Nomor WhatsApp
            <input type="tel" id="input-nomorhp" name="nomorhp" required maxlength="20" value="{{ old('nomorhp', $tenant->nomorhp) }}" class="w-full rounded-lg border border-bq-border bg-bq-surface p-2">
        </label>
        <label class="grid gap-1 text-sm">Alamat
            <input id="input-alamat" name="alamat" required maxlength="255" value="{{ old('alamat', $tenant->alamat) }}" class="w-full rounded-lg border border-bq-border bg-bq-surface p-2">
        </label>
        <label class="grid gap-1 text-sm sm:col-span-2">Deskripsi
            <textarea name="deskripsi" maxlength="1000" rows="3" class="w-full rounded-lg border border-bq-border bg-bq-surface p-2">{{ old('deskripsi', $tenant->deskripsi) }}</textarea>
        </label>
    </div>
    <p class="text-sm text-bq-text-muted">Zona waktu: WIB.</p>
    @if ($storefrontUrl)
        <p class="text-sm">Tautan booking: <a class="break-all text-bq-primary underline" href="{{ $storefrontUrl }}">{{ $storefrontUrl }}</a></p>
        <p class="text-sm text-bq-text-muted">Perubahan nama dapat mengubah tautan booking. Tautan, kode QR, dan poster lama akan berhenti berfungsi jika alamat berubah.</p>
    @endif
    <button class="rounded-lg bg-bq-primary px-4 py-2 text-sm font-semibold text-white">Simpan profil</button>
</form>
<x-owner.dialog id="settings-rename" title="Ubah nama bisnis?" description="Jika alamat booking berubah, tautan lama, kode QR, dan poster lama berhenti berfungsi.">
    <button form="settings-profile" type="submit" data-rename-confirmed class="rounded-lg bg-bq-primary px-4 py-2 text-sm font-semibold text-white">Simpan perubahan nama</button>
</x-owner.dialog>
@if ($canManageTeam)
    <a href="{{ route('owner.team.index') }}" class="mt-4 inline-block text-sm font-semibold text-bq-primary underline">Kelola tim</a>
@endif
