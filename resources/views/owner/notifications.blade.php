@extends('layouts.owner-layout')

@section('title', 'Notifikasi')

{{--
    Notifikasi (BQ-145, owner-portal.md §8.16): every notification meant for this role, with the
    unread and type filters. Opening an item marks it read and lands on its target (AC-NTF-01);
    delete goes through the shared confirm dialog (BQ-102). Data from App\Actions\Team\GetNotificationsPage.
--}}

@php
    $chip = fn (bool $active): string => 'shrink-0 rounded-full border px-3 py-1.5 text-sm font-medium '.($active ? 'border-bq-primary bg-bq-primary-light text-bq-primary' : 'border-bq-border bg-bq-surface text-bq-text-muted hover:text-bq-text');
    $query = fn (array $change): array => array_filter(array_merge(['filter' => $filter === 'unread' ? 'unread' : null, 'type' => $type], $change));
@endphp

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <x-owner.page-header title="Notifikasi" purpose="Apa yang terjadi dan perlu Anda ketahui.">
        @if ($unreadCount > 0)
            <x-slot:action>
                <form method="POST" action="{{ route('owner.notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center rounded-lg border border-bq-border bg-bq-surface px-3 py-1.5 text-sm font-medium text-bq-text hover:bg-bq-background">Tandai semua dibaca</button>
                </form>
            </x-slot:action>
        @endif
    </x-owner.page-header>

    <nav aria-label="Filter notifikasi" class="flex flex-wrap gap-2">
        <a href="{{ route('owner.notifications', $query(['filter' => null])) }}" class="{{ $chip($filter === 'all') }}" @if ($filter === 'all') aria-current="page" @endif>Semua</a>
        <a href="{{ route('owner.notifications', $query(['filter' => 'unread'])) }}" class="{{ $chip($filter === 'unread') }}" @if ($filter === 'unread') aria-current="page" @endif>Belum dibaca ({{ $unreadCount }})</a>
        <span class="mx-1 hidden w-px bg-bq-border sm:block" aria-hidden="true"></span>
        <a href="{{ route('owner.notifications', $query(['type' => null])) }}" class="{{ $chip($type === null) }}">Semua jenis</a>
        @foreach ($typeFilters as $key => $label)
            <a href="{{ route('owner.notifications', $query(['type' => $key])) }}" class="{{ $chip($type === $key) }}" @if ($type === $key) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>

    @if ($notifications->total() === 0)
        <x-owner.empty-state
            :title="$filter === 'unread' ? 'Semua notifikasi sudah dibaca' : 'Belum ada notifikasi'"
            description="Booking baru, perubahan status, refund, dan kabar paket muncul di sini."
        />
    @else
        <ul class="divide-y divide-bq-border overflow-hidden rounded-xl border border-bq-border bg-bq-surface" aria-label="Daftar notifikasi">
            @foreach ($notifications as $item)
                <li class="flex items-start gap-3 px-4 py-3 {{ $item['unread'] ? 'bg-bq-primary-light/40' : '' }}">
                    <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $item['unread'] ? 'bg-bq-primary' : 'bg-transparent' }}" aria-hidden="true"></span>
                    <form method="POST" action="{{ $item['readUrl'] }}" class="min-w-0 flex-1">
                        @csrf
                        <button type="submit" class="block w-full text-left">
                            <span class="block text-sm {{ $item['unread'] ? 'font-semibold' : 'font-medium' }} text-bq-text">{{ $item['title'] }}<span class="sr-only">{{ $item['unread'] ? ' (belum dibaca)' : '' }}</span></span>
                            @if ($item['message'] !== '')
                                <span class="block text-sm text-bq-text-muted">{{ $item['message'] }}</span>
                            @endif
                            <span class="mt-0.5 block text-xs text-bq-text-muted">{{ $item['category'] }} · {{ $item['time'] }}</span>
                        </button>
                    </form>
                    <button
                        type="button"
                        class="shrink-0 rounded-lg px-2 py-1 text-xs text-bq-text-muted hover:bg-bq-background hover:text-bq-danger"
                        aria-label="Hapus notifikasi {{ $item['title'] }}"
                        x-data
                        x-on:click="$dispatch('open-confirm', { title: 'Hapus notifikasi?', message: 'Notifikasi ini akan dihapus dari daftar Anda.', actionUrl: @js($item['destroyUrl']), method: 'DELETE', trigger: $el })"
                    >Hapus</button>
                </li>
            @endforeach
        </ul>

        <div>{{ $notifications->links() }}</div>
    @endif
</div>
@endsection
