@extends('layouts.owner-layout')

@section('title', 'Tim')

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <header>
        <a href="{{ route('owner.settings') }}" class="text-indigo-600">Kembali ke Pengaturan</a>
        <h1 class="text-2xl font-bold">Tim</h1>
        <p>Batas akun tim di luar Pemilik: {{ $seatLimit }}.</p>
    </header>
    @if (session('sukses')) <p role="status">{{ session('sukses') }}</p> @endif
    @foreach ($errors->all() as $error) <p role="alert" class="text-red-700">{{ $error }}</p> @endforeach
    @if ($seatLimit > 0)
        <form method="POST" action="{{ route('owner.team.invite') }}" class="flex flex-wrap items-end gap-3 rounded-xl border p-4">
            @csrf
            <label>Email <input name="email" type="email" value="{{ old('email') }}" required class="block rounded border p-2"></label>
            <label>Peran <select name="role" class="block rounded border p-2"><option value="kasir">Kasir</option><option value="manajer">Manajer</option></select></label>
            <button class="rounded bg-indigo-700 px-4 py-2 text-white">Kirim undangan</button>
        </form>
    @endif
    <section class="space-y-3 rounded-xl border p-4">
        <h2 class="text-lg font-semibold">Anggota aktif</h2>
        @foreach ($members as $member)
            <div class="flex flex-wrap items-center gap-3">
                <span>{{ $member->user?->namalengkap }} · {{ ucfirst($member->role) }}</span>
                @unless (\App\Domain\Team\TenantPermission::isOwnerRole($member->role))
                    <form method="POST" action="{{ route('owner.team.change', $member->id) }}" class="flex items-center gap-2">
                        @csrf @method('PATCH')
                        <select name="role" aria-label="Peran {{ $member->user?->namalengkap }}" class="rounded border p-2">
                            <option value="kasir" @selected($member->role === 'kasir')>Kasir</option>
                            <option value="manajer" @selected($member->role === 'manajer')>Manajer</option>
                        </select>
                        <button class="text-indigo-600">Simpan peran</button>
                    </form>
                    <form method="POST" action="{{ route('owner.team.revoke', $member->id) }}">
                        @csrf @method('DELETE')
                        <button class="text-red-700">Cabut akses</button>
                    </form>
                @endunless
            </div>
        @endforeach
    </section>
    <section class="space-y-3 rounded-xl border p-4">
        <h2 class="text-lg font-semibold">Undangan menunggu</h2>
        @forelse ($invitations as $invitation)
            <form method="POST" action="{{ route('owner.team.invitation-revoke', $invitation->id) }}" class="flex flex-wrap items-center gap-3">
                @csrf @method('DELETE')
                <span>{{ $invitation->email }} · {{ ucfirst($invitation->role) }}</span>
                <button class="text-red-700">Batalkan undangan</button>
            </form>
        @empty
            <p>Belum ada undangan yang menunggu.</p>
        @endforelse
    </section>
    <section class="space-y-3 rounded-xl border p-4">
        <h2 class="text-lg font-semibold">Riwayat aktivitas</h2>
        @forelse ($activity as $entry)
            <p>{{ $entry->created_at }} · {{ $entry->actor_name ?? 'Akun tidak tersedia' }} ({{ ucfirst($entry->role) }}) · {{ __($entry->event) }} @if ($entry->subject_id) #{{ $entry->subject_id }} @endif</p>
        @empty
            <p>Belum ada aktivitas yang tercatat.</p>
        @endforelse
        {{ $activity->links() }}
    </section>
</div>
@endsection
