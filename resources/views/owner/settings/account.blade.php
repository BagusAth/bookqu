<form method="POST" action="{{ route('owner.settings.account') }}"
    x-data="{ password: '' }">
    @csrf
    <x-ui.form-row title="Data diri">
    <div class="grid gap-3 sm:grid-cols-2">
        <label class="grid gap-1 text-sm">Nama lengkap
            <input name="namalengkap" required value="{{ old('namalengkap', $accountUser?->namalengkap) }}" autocomplete="name" class="bq-input w-full">
            <x-owner.field-error name="namalengkap" />
        </label>
        <label class="grid gap-1 text-sm">Email
            <input type="email" name="email" required value="{{ old('email', $accountUser?->email) }}" autocomplete="email" @readonly(! ($accountUser?->hasPassword() ?? true)) class="bq-input w-full">
            <x-owner.field-error name="email" />
        </label>
        <label class="grid gap-1 text-sm">Nomor telepon
            <input type="tel" name="nomorhp" required value="{{ old('nomorhp', $accountUser?->nomorhp) }}" autocomplete="tel" class="bq-input w-full">
            <x-owner.field-error name="nomorhp" />
        </label>
    </div>
    </x-ui.form-row>
    @php($accountHasPassword = $accountUser?->hasPassword() ?? true)
    @unless ($accountHasPassword)
    {{-- BQ-172 (D-029): an account created through Google sets its first password through the email link. --}}
    <x-ui.form-row title="Kata sandi" description="Akun ini masuk lewat Google dan belum punya kata sandi.">
        <div class="grid gap-2 text-sm">
            <p class="text-bq-text-muted">Buat kata sandi dulu untuk mengganti email, menghapus akun, dan mengubah rekening penarikan. Kami kirim link ke {{ $accountUser?->email }}; Anda akan keluar lalu masuk lagi setelah membuatnya.</p>
            <div><x-ui.button type="submit" variant="secondary" form="settings-password-link">Kirim link buat kata sandi</x-ui.button></div>
        </div>
    </x-ui.form-row>
    @else
    <x-ui.form-row title="Kata sandi" description="Kosongkan kata sandi baru bila tidak ingin menggantinya.">
    <div class="grid gap-3 sm:grid-cols-2">
        <label class="grid gap-1 text-sm sm:col-span-2">Kata sandi saat ini
            <input type="password" name="current_password" autocomplete="current-password"
                required class="bq-input w-full">
            <x-owner.field-error name="current_password" />
            <span class="text-bq-text-muted">Wajib untuk menyimpan perubahan akun.</span>
        </label>
        <label class="grid gap-1 text-sm">Kata sandi baru (opsional)
            <input type="password" name="password" x-model="password" autocomplete="new-password" minlength="8" class="bq-input w-full">
            <x-owner.field-error name="password" />
        </label>
        <label class="grid gap-1 text-sm">Ulangi kata sandi baru
            <input type="password" name="password_confirmation" autocomplete="new-password" :required="password !== ''" class="bq-input w-full">
            <x-owner.field-error name="password_confirmation" />
        </label>
    </div>
    </x-ui.form-row>
    @endunless
    @if ($canDeleteBusiness && $accountHasPassword)
        <x-ui.form-row title="Hapus akun bisnis" description="Tindakan ini menghapus akun dan data bisnis secara permanen.">
            <div>
                <x-ui.button variant="danger" @click="$dispatch('open-dialog', { id: 'settings-delete-business' })">Hapus akun bisnis</x-ui.button>
            </div>
        </x-ui.form-row>
    @endif
    <x-ui.savebar class="!static flex-wrap">
        <x-ui.button variant="ghost" :href="route('owner.settings', ['tab' => 'account'])">Batal</x-ui.button>
        <x-ui.button type="submit" variant="primary">Simpan akun</x-ui.button>
    </x-ui.savebar>
</form>

@unless ($accountHasPassword)
    <form id="settings-password-link" method="POST" action="{{ route('owner.settings.account.password-link') }}" class="hidden">
        @csrf
    </form>
@endunless

@if ($canDeleteBusiness)
    <x-owner.dialog id="settings-delete-business" reopen-for="settings-delete-business" title="Hapus akun bisnis" description="Masukkan email akun dan kata sandi saat ini untuk mengonfirmasi.">
        <form method="POST" action="{{ route('owner.settings.account.delete') }}" class="space-y-3">
            @csrf
            @method('DELETE')
            <input type="hidden" name="_form" value="settings-delete-business">
            <x-owner.field-error name="error" form="settings-delete-business" />
            <label class="grid gap-1 text-sm">Email akun
                <input type="email" name="confirm_account" value="{{ old('confirm_account') }}" required autocomplete="email" class="bq-input w-full">
                <x-owner.field-error name="confirm_account" />
            </label>
            <label class="grid gap-1 text-sm">Kata sandi saat ini
                <input type="password" name="delete_password" required autocomplete="current-password" class="bq-input w-full">
                <x-owner.field-error name="delete_password" />
            </label>
            <button class="bq-btn bq-btn-danger-solid">Hapus permanen</button>
        </form>
    </x-owner.dialog>
@endif
