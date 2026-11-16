<div class="space-y-5">
    <div>
        <h2 class="text-lg font-semibold">Tim</h2>
        <p class="text-sm text-bq-text-muted">{{ $seatUsage }} dari {{ $seatLimit }} kursi digunakan, termasuk undangan menunggu.</p>
    </div>
    @if ($seatLimit > 0)
        <form method="POST" action="{{ route('owner.team.invite') }}" class="flex flex-wrap items-end gap-3 rounded-xl border border-bq-border bg-bq-surface p-4">
            @csrf
            <label>Email <input name="email" type="email" value="{{ old('email') }}" required class="block rounded-lg border border-bq-border bg-bq-surface p-2"></label>
            <label>Peran <select name="role" class="block rounded-lg border border-bq-border bg-bq-surface p-2"><option value="kasir">Kasir</option><option value="manajer">Manajer</option></select></label>
            <button class="rounded bg-bq-primary px-4 py-2 text-white">Kirim undangan</button>
        </form>
    @endif
    <section class="space-y-3 rounded-xl border border-bq-border bg-bq-surface p-4">
        <h2 class="text-lg font-semibold">Anggota aktif</h2>
        @foreach ($members as $member)
            <div class="flex flex-wrap items-center gap-3">
                <span>{{ $member->user?->namalengkap }} · {{ ucfirst($member->role) }}</span>
                <x-owner.status-badge type="availability" status="active" />
                @unless (\App\Domain\Team\TenantPermission::isOwnerRole($member->role))
                    <form method="POST" action="{{ route('owner.team.change', $member->id) }}" class="flex items-center gap-2">
                        @csrf @method('PATCH')
                        <select name="role" aria-label="Peran {{ $member->user?->namalengkap }}" class="rounded-lg border border-bq-border bg-bq-surface p-2">
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
    </section>
    <section class="space-y-3 rounded-xl border border-bq-border bg-bq-surface p-4">
        <h2 class="text-lg font-semibold">Undangan menunggu</h2>
        @forelse ($invitations as $invitation)
            <form method="POST" action="{{ route('owner.team.invitation-revoke', $invitation->id) }}" id="team-invite-revoke-{{ $invitation->id }}" x-data class="flex flex-wrap items-center gap-3">
                @csrf @method('DELETE')
                <span>{{ $invitation->email }} · {{ ucfirst($invitation->role) }}</span>
                <button type="button" class="text-bq-danger" @click="$dispatch('open-confirm', { title: 'Batalkan undangan?', message: 'Tautan undangan ini akan kedaluwarsa.', formId: $el.form.id, tone: 'danger' })">Batalkan undangan</button>
            </form>
        @empty
            <p>Belum ada undangan yang menunggu.</p>
        @endforelse
    </section>
    <section class="space-y-3 rounded-xl border border-bq-border bg-bq-surface p-4">
        <h2 class="text-lg font-semibold">Riwayat aktivitas</h2>
        @forelse ($activity as $entry)
            <p>{{ $entry->local_time }} · {{ $entry->actor_name ?? 'Akun tidak tersedia' }} ({{ ucfirst($entry->role) }}) · {{ $entry->label }}</p>
        @empty
            <p>Belum ada aktivitas yang tercatat.</p>
        @endforelse
        {{ $activity->links() }}
    </section>
</div>
