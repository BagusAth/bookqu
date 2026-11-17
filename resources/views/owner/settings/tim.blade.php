<x-ui.form-row title="Kelola tim" :description="$seatUsage.' dari '.$seatLimit.' kursi digunakan, termasuk undangan menunggu.'">
    <div><x-ui.button :href="route('owner.team.index')">Kelola tim</x-ui.button></div>
</x-ui.form-row>
@if ($seatLimit > 0)
    <form method="POST" action="{{ route('owner.team.invite') }}">
        @csrf
        <x-ui.form-row title="Undang anggota">
            <div class="grid gap-3 sm:grid-cols-2">
                <label class="grid gap-1 text-sm">Email <x-ui.input name="email" type="email" :value="old('email')" required /></label>
                <label class="grid gap-1 text-sm">Peran <x-ui.select name="role"><option value="kasir">Kasir</option><option value="manajer">Manajer</option></x-ui.select></label>
            </div>
        </x-ui.form-row>
        <x-ui.savebar class="!static flex-wrap">
        <x-ui.button variant="ghost" :href="route('owner.settings.business', ['tab' => 'tim'])">Batal</x-ui.button>
        <x-ui.button type="submit" variant="primary">Kirim undangan</x-ui.button>
    </x-ui.savebar>
    </form>
@endif
    <x-ui.form-row title="Anggota aktif">
        @foreach ($members as $member)
            <div class="flex flex-wrap items-center gap-3">
                <span>{{ $member->user?->namalengkap }} · {{ ucfirst($member->role) }}</span>
                <x-owner.status-badge type="availability" status="active" />
                @unless (\App\Domain\Team\TenantPermission::isOwnerRole($member->role))
                    <form method="POST" action="{{ route('owner.team.change', $member->id) }}" class="flex flex-wrap items-center gap-2">
                        @csrf @method('PATCH')
                        <select name="role" aria-label="Peran {{ $member->user?->namalengkap }}" class="bq-input">
                            <option value="kasir" @selected($member->role === 'kasir')>Kasir</option>
                            <option value="manajer" @selected($member->role === 'manajer')>Manajer</option>
                        </select>
                        <button class="text-bq-primary">Simpan peran</button>
                    </form>
                    <form method="POST" action="{{ route('owner.team.revoke', $member->id) }}" id="team-revoke-{{ $member->id }}" x-data>
                        @csrf @method('DELETE')
                        <button type="button" class="text-bq-danger" @click="$dispatch('open-confirm', { title: 'Cabut akses anggota?', message: 'Anggota akan kehilangan akses ke bisnis ini.', formId: $el.form.id, tone: 'danger' })">Cabut akses</button>
                    </form>
                @endunless
            </div>
        @endforeach
    </x-ui.form-row>
    <x-ui.form-row title="Undangan menunggu">
        @forelse ($invitations as $invitation)
            <form method="POST" action="{{ route('owner.team.invitation-revoke', $invitation->id) }}" id="team-invite-revoke-{{ $invitation->id }}" x-data class="flex flex-wrap items-center gap-3">
                @csrf @method('DELETE')
                <span>{{ $invitation->email }} · {{ ucfirst($invitation->role) }}</span>
                <button type="button" class="text-bq-danger" @click="$dispatch('open-confirm', { title: 'Batalkan undangan?', message: 'Tautan undangan ini akan kedaluwarsa.', formId: $el.form.id, tone: 'danger' })">Batalkan undangan</button>
            </form>
        @empty
            <p>Belum ada undangan yang menunggu.</p>
        @endforelse
    </x-ui.form-row>
    <x-ui.form-row title="Riwayat aktivitas">
        @forelse ($activity as $entry)
            <p>{{ $entry->local_time }} · {{ $entry->actor_name ?? 'Akun tidak tersedia' }} ({{ ucfirst($entry->role) }}) · {{ $entry->label }}</p>
        @empty
            <p>Belum ada aktivitas yang tercatat.</p>
        @endforelse
        {{ $activity->links() }}
    </x-ui.form-row>
