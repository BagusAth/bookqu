@extends('layouts.owner-layout')

@section('title', 'Notifikasi')

{{--
    Notifikasi (BQ-145, owner-portal.md §8.16): every notification meant for this role, with the
    unread and type filters. Opening an item marks it read and lands on its target (AC-NTF-01);
    delete goes through the shared confirm dialog (BQ-102). Data from App\Actions\Team\GetNotificationsPage.
--}}

@php
    $query = fn (array $change): array => array_filter(array_merge(['filter' => $filter === 'unread' ? 'unread' : null, 'type' => $type], $change));
@endphp

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <x-owner.page-header title="Notifikasi" purpose="Yang terjadi dan perlu Anda ketahui.">
        <x-slot:action>
            <form method="POST" action="{{ route('owner.notifications.read-all') }}">
                @csrf
                <x-ui.button type="submit" variant="ghost" size="sm" icon="check" :disabled="$unreadCount === 0">Tandai semua dibaca</x-ui.button>
            </form>
        </x-slot:action>
    </x-owner.page-header>

    <div class="bq-toolbar justify-between">
        <x-ui.segmented label="Status notifikasi" :selected="$filter" :options="[
            'all' => ['label' => 'Semua', 'href' => route('owner.notifications', $query(['filter' => null]))],
            'unread' => ['label' => 'Belum dibaca ('.$unreadCount.')', 'href' => route('owner.notifications', $query(['filter' => 'unread']))],
        ]" />
        <form method="GET" action="{{ route('owner.notifications') }}" class="ml-auto min-w-0">
            @if ($filter === 'unread')<input type="hidden" name="filter" value="unread">@endif
            <x-ui.select name="type" aria-label="Jenis notifikasi" x-data x-on:change="$el.form.requestSubmit()">
                <option value="">Semua jenis</option>
                @foreach ($typeFilters as $key => $label)
                    <option value="{{ $key }}" @selected($type === $key)>{{ $label }}</option>
                @endforeach
            </x-ui.select>
            <noscript><x-ui.button type="submit" size="sm" class="mt-2">Terapkan</x-ui.button></noscript>
        </form>
    </div>

    @if ($notifications->total() === 0)
        <x-owner.empty-state
            :title="$filter === 'unread' ? 'Semua notifikasi sudah dibaca' : 'Belum ada notifikasi'"
            description="Booking baru, perubahan status, refund, dan kabar paket muncul di sini."
        />
    @else
        <x-ui.panel flush>
            <ul class="divide-y divide-bq-border" aria-label="Daftar notifikasi">
                @foreach ($notifications->getCollection()->groupBy('day') as $day => $items)
                    <li>
                        <h2 class="bg-bq-surface-2 px-4 py-2 text-bq-small font-semibold">{{ $day }}</h2>
                        <ul class="divide-y divide-bq-border">
                            @foreach ($items as $item)
                                <li class="flex items-start gap-2 px-3 py-3 sm:items-center sm:gap-3 sm:px-4 {{ $item['unread'] ? 'bg-bq-primary-light/40' : '' }}">
                                    <span class="grid size-8 shrink-0 place-items-center rounded-full bg-bq-surface-2 text-bq-primary">
                                        <x-owner.icon :name="$item['icon']" class="size-4" />
                                    </span>
                                    <form method="POST" action="{{ $item['readUrl'] }}" class="min-w-0 flex-1">
                                        @csrf
                                        <button type="submit" class="flex w-full min-w-0 flex-wrap items-center justify-between gap-x-3 gap-y-1 text-left">
                                            <span class="min-w-0 basis-56 grow break-words">
                                                <span class="block text-sm {{ $item['unread'] ? 'font-semibold' : 'font-medium' }} text-bq-text">{{ $item['title'] }}</span>
                                                @if ($item['message'] !== '')
                                                    <span class="block text-sm text-bq-text-muted">{{ $item['message'] }}</span>
                                                @endif
                                            </span>
                                            @if ($item['time'])
                                                <time datetime="{{ $item['dateTime'] }}" title="{{ $item['timeLabel'] }}" class="shrink-0 text-bq-small bq-num text-bq-text-muted">{{ $item['time'] }}</time>
                                            @endif
                                        </button>
                                    </form>
                                    @if ($item['unread'])
                                        <span class="mt-3 size-2 shrink-0 rounded-full bg-bq-primary sm:mt-0"><span class="sr-only">Belum dibaca</span></span>
                                    @endif
                                    <x-ui.menu :id="'notification-menu-'.$item['id']" :label="'Opsi '.$item['title']" class="shrink-0">
                                        @if ($item['unread'])
                                            <form method="POST" action="{{ $item['readUrl'] }}" role="none">
                                                @csrf
                                                <input type="hidden" name="redirect" value="{{ route('owner.notifications', $query([])) }}">
                                                <button type="submit" role="menuitem" tabindex="-1" class="w-full text-bq-body">Tandai dibaca</button>
                                            </form>
                                        @endif
                                        <button type="button" role="menuitem" tabindex="-1" class="text-bq-body text-bq-danger"
                                            x-on:click="$dispatch('open-confirm', { title: 'Hapus notifikasi?', message: 'Notifikasi ini akan dihapus dari daftar Anda.', actionUrl: @js($item['destroyUrl']), method: 'DELETE', trigger: $refs.trigger })"
                                        >Hapus</button>
                                    </x-ui.menu>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                @endforeach
            </ul>
            <x-ui.pager :paginator="$notifications" noun="notifikasi" />
        </x-ui.panel>
    @endif
</div>
@endsection
