<form method="POST" action="{{ route('owner.settings.account') }}" class="space-y-4 rounded-xl border border-bq-border bg-bq-surface p-4"
    x-data="{ email: @js(old('email', $accountUser?->email)), password: '', needsPassword: @js($errors->has('current_password')) }">
    @csrf
    <h2 class="font-semibold">Akun</h2>
    <div class="grid gap-3 sm:grid-cols-2">
        <label class="grid gap-1 text-sm">Nama lengkap
            <input name="namalengkap" required value="{{ old('namalengkap', $accountUser?->namalengkap) }}" autocomplete="name" class="w-full rounded-lg border border-bq-border bg-bq-surface p-2">
        </label>
        <label class="grid gap-1 text-sm">Email
            <input type="email" name="email" required x-model="email" value="{{ old('email', $accountUser?->email) }}" autocomplete="email" class="w-full rounded-lg border border-bq-border bg-bq-surface p-2">
        </label>
        <label class="grid gap-1 text-sm">Nomor telepon
            <input type="tel" name="nomorhp" required value="{{ old('nomorhp', $accountUser?->nomorhp) }}" autocomplete="tel" class="w-full rounded-lg border border-bq-border bg-bq-surface p-2">
        </label>
        <label class="grid gap-1 text-sm">Kata sandi saat ini
            <input type="password" name="current_password" autocomplete="current-password"
                :required="needsPassword || password !== '' || (email || '').trim().toLowerCase() !== @js(strtolower(trim($accountUser?->email ?? '')))" class="w-full rounded-lg border border-bq-border bg-bq-surface p-2">
            <span class="text-bq-text-muted">Wajib untuk mengubah email atau kata sandi.</span>
        </label>
        <label class="grid gap-1 text-sm">Kata sandi baru (opsional)
            <input type="password" name="password" x-model="password" autocomplete="new-password" minlength="8" class="w-full rounded-lg border border-bq-border bg-bq-surface p-2">
        </label>
        <label class="grid gap-1 text-sm">Ulangi kata sandi baru
            <input type="password" name="password_confirmation" autocomplete="new-password" :required="password !== ''" class="w-full rounded-lg border border-bq-border bg-bq-surface p-2">
        </label>
    </div>
    <button class="rounded-lg bg-bq-primary px-4 py-2 text-sm font-semibold text-white">Simpan akun</button>
</form>

@if ($canDeleteBusiness)
    <div x-data class="mt-4 rounded-xl border border-bq-border bg-bq-surface p-4">
        <h2 class="font-semibold">Hapus akun bisnis</h2>
        <p class="my-2 text-sm text-bq-text-muted">Tindakan ini menghapus akun dan data bisnis secara permanen.</p>
        <button type="button" @click="$dispatch('open-dialog', { id: 'settings-delete-business' })" class="text-sm font-semibold text-bq-danger">Hapus akun bisnis</button>
    </div>
    <x-owner.dialog id="settings-delete-business" title="Hapus akun bisnis" description="Masukkan email akun dan kata sandi saat ini untuk mengonfirmasi.">
        <form method="POST" action="{{ route('owner.settings.account.delete') }}" class="space-y-3">
            @csrf
            @method('DELETE')
            <label class="grid gap-1 text-sm">Email akun
                <input type="email" name="confirm_account" required autocomplete="email" class="w-full rounded-lg border border-bq-border bg-bq-surface p-2">
            </label>
            <label class="grid gap-1 text-sm">Kata sandi saat ini
                <input type="password" name="delete_password" required autocomplete="current-password" class="w-full rounded-lg border border-bq-border bg-bq-surface p-2">
            </label>
            <button class="rounded-lg bg-bq-danger px-4 py-2 text-sm font-semibold text-white">Hapus permanen</button>
        </form>
    </x-owner.dialog>
@endif
