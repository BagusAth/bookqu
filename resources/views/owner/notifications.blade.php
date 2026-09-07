@extends('layouts.owner-layout')

@section('title', 'Pusat Notifikasi')

@section('content')
<div class="mx-auto max-w-5xl space-y-6">

    {{-- Page Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2.5">
                <h1 class="text-2xl font-black text-[#231a3d] sm:text-3xl" id="notifications-title">
                    Pusat Notifikasi
                </h1>
                @if ($unreadCount > 0)
                    <span class="inline-flex items-center rounded-full bg-rose-500 px-2.5 py-0.5 text-xs font-bold text-white shadow-xs">
                        {{ $unreadCount }} Baru
                    </span>
                @endif
            </div>
            <p class="mt-1 text-xs text-[#6e6584] sm:text-sm">
                Pantau setiap booking masuk dan perubahan jadwal atau status reservasi secara langsung.
            </p>
        </div>

        @if ($unreadCount > 0)
            <div>
                <form action="{{ route('owner.notifications.read-all') }}" method="POST">
                    @csrf
                    <button
                        type="submit"
                        class="craft-btn inline-flex items-center gap-2 rounded-xl border border-[#b499ff]/40 bg-[#f3effe] px-4 py-2 text-xs font-bold text-[#382186] hover:bg-[#e7e2f7] hover:border-[#382186] transition-all shadow-2xs cursor-pointer active:scale-95"
                        id="btn-page-mark-all-read"
                    >
                        <svg class="h-4 w-4 text-[#382186]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Tandai Semua Dibaca</span>
                    </button>
                </form>
            </div>
        @endif
    </div>

    {{-- Filters Bar --}}
    <div class="flex flex-wrap items-center gap-2 border-b border-[#e7e2f7] pb-3" id="notification-filters">
        <a
            href="{{ route('owner.notifications', ['filter' => 'all']) }}"
            class="craft-btn inline-flex items-center gap-1.5 rounded-xl px-3.5 py-1.5 text-xs font-bold transition-all {{ $filter === 'all' ? 'bg-[#382186] text-white shadow-2xs' : 'bg-white text-[#6e6584] border border-[#e7e2f7] hover:border-[#b499ff] hover:text-[#231a3d]' }}"
        >
            <span>Semua</span>
            <span class="ml-1 rounded-full px-1.5 py-0.2 text-[10px] {{ $filter === 'all' ? 'bg-white/20 text-white' : 'bg-[#f7f7fa] text-[#6e6584]' }}">{{ $totalCount }}</span>
        </a>

        <a
            href="{{ route('owner.notifications', ['filter' => 'unread']) }}"
            class="craft-btn inline-flex items-center gap-1.5 rounded-xl px-3.5 py-1.5 text-xs font-bold transition-all {{ $filter === 'unread' ? 'bg-[#382186] text-white shadow-2xs' : 'bg-white text-[#6e6584] border border-[#e7e2f7] hover:border-[#b499ff] hover:text-[#231a3d]' }}"
        >
            <span>Belum Dibaca</span>
            @if ($unreadCount > 0)
                <span class="ml-1 rounded-full px-1.5 py-0.2 text-[10px] bg-rose-500 text-white font-black">{{ $unreadCount }}</span>
            @endif
        </a>

        <a
            href="{{ route('owner.notifications', ['filter' => 'new_booking']) }}"
            class="craft-btn inline-flex items-center gap-1.5 rounded-xl px-3.5 py-1.5 text-xs font-bold transition-all {{ $filter === 'new_booking' ? 'bg-[#382186] text-white shadow-2xs' : 'bg-white text-[#6e6584] border border-[#e7e2f7] hover:border-[#b499ff] hover:text-[#231a3d]' }}"
        >
            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
            <span>Booking Masuk</span>
        </a>

        <a
            href="{{ route('owner.notifications', ['filter' => 'status_change']) }}"
            class="craft-btn inline-flex items-center gap-1.5 rounded-xl px-3.5 py-1.5 text-xs font-bold transition-all {{ $filter === 'status_change' ? 'bg-[#382186] text-white shadow-2xs' : 'bg-white text-[#6e6584] border border-[#e7e2f7] hover:border-[#b499ff] hover:text-[#231a3d]' }}"
        >
            <span class="h-2 w-2 rounded-full bg-amber-500"></span>
            <span>Perubahan Status</span>
        </a>
    </div>

    {{-- Notifications List --}}
    @if ($notifications->isEmpty())
        <div class="rounded-2xl border border-[#e7e2f7] bg-white p-12 text-center shadow-2xs" id="empty-notifications-card">
            <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-[#f3effe] text-[#382186]">
                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
            </div>
            <h3 class="text-base font-bold text-[#231a3d]">Tidak Ada Notifikasi</h3>
            <p class="mt-1 text-xs text-[#6e6584] max-w-sm mx-auto">
                @if ($filter === 'unread')
                    Semua notifikasi telah dibaca. Anda dapat melihat kembali notifikasi terdahulu dengan memilih filter "Semua".
                @else
                    Saat ada pemesanan baru atau perubahan status reservasi, pemberitahuan akan dicatat secara otomatis di sini.
                @endif
            </p>
        </div>
    @else
        <div class="space-y-3" id="notifications-container">
            @foreach ($notifications as $notification)
                @php
                    $data = $notification->data ?? [];
                    $isUnread = is_null($notification->read_at);
                    $eventType = $data['event_type'] ?? 'info';
                    $targetUrl = $data['url'] ?? route('owner.bookings');

                    $iconBg = match($eventType) {
                        'new_booking' => 'bg-emerald-50 text-emerald-600 border-emerald-200',
                        'cancelled'   => 'bg-rose-50 text-rose-600 border-rose-200',
                        'rescheduled' => 'bg-amber-50 text-amber-600 border-amber-200',
                        'completed'   => 'bg-indigo-50 text-indigo-600 border-indigo-200',
                        default       => 'bg-[#f3effe] text-[#382186] border-[#b499ff]/30',
                    };

                    $badgePill = match($eventType) {
                        'new_booking' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                        'cancelled'   => 'bg-rose-50 text-rose-700 border-rose-200',
                        'rescheduled' => 'bg-amber-50 text-amber-700 border-amber-200',
                        'completed'   => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                        default       => 'bg-gray-50 text-gray-700 border-gray-200',
                    };
                @endphp

                <div class="group relative rounded-2xl border transition-all shadow-2xs {{ $isUnread ? 'border-[#b499ff]/50 bg-[#faf9ff]' : 'border-[#e7e2f7] bg-white hover:border-[#b499ff]/40' }} p-4 sm:p-5">
                    <div class="flex items-start gap-3.5 sm:gap-4">
                        {{-- Icon Box --}}
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border {{ $iconBg }} shadow-2xs">
                            @if ($eventType === 'new_booking')
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            @elseif ($eventType === 'cancelled')
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            @elseif ($eventType === 'rescheduled')
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            @else
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            @endif
                        </div>

                        {{-- Details --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center rounded-lg border px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wide {{ $badgePill }}">
                                        {{ $data['title'] ?? 'Notifikasi' }}
                                    </span>
                                    @if ($isUnread)
                                        <span class="inline-flex items-center rounded-md bg-purple-600 px-1.5 py-0.5 text-[9px] font-black text-white">
                                            BARU
                                        </span>
                                    @endif
                                </div>
                                <span class="text-xs text-[#6e6584]" title="{{ $notification->created_at->format('d M Y H:i:s') }}">
                                    {{ $notification->created_at->diffForHumans() }}
                                </span>
                            </div>

                            <p class="mt-1.5 text-sm font-semibold text-[#231a3d]">
                                {{ $data['message'] ?? '-' }}
                            </p>

                            {{-- Metadata Badges --}}
                            <div class="mt-2.5 flex flex-wrap items-center gap-3 text-xs text-[#6e6584]">
                                @if (!empty($data['customer_name']))
                                    <span class="inline-flex items-center gap-1">
                                        <svg class="h-3.5 w-3.5 text-[#382186]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        <span class="font-medium text-[#231a3d]">{{ $data['customer_name'] }}</span>
                                    </span>
                                @endif

                                @if (!empty($data['booking_code']))
                                    <span class="inline-flex items-center gap-1 font-mono text-[11px] bg-[#f7f7fa] px-2 py-0.5 rounded-md border border-[#e7e2f7]">
                                        <span class="text-[#6e6584]">Kode:</span>
                                        <span class="font-bold text-[#382186]">{{ $data['booking_code'] }}</span>
                                    </span>
                                @endif

                                @if (!empty($data['service_name']))
                                    <span class="inline-flex items-center gap-1">
                                        <span class="text-[#6e6584]">Layanan:</span>
                                        <span class="font-medium text-[#231a3d]">{{ $data['service_name'] }}</span>
                                    </span>
                                @endif

                                @if (!empty($data['tanggal']))
                                    <span class="inline-flex items-center gap-1">
                                        <svg class="h-3.5 w-3.5 text-[#6e6584]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        <span>{{ $data['tanggal'] }} {{ $data['jam'] ?? '' }}</span>
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Action Controls --}}
                        <div class="flex flex-col sm:flex-row items-center gap-1.5 shrink-0 self-center sm:self-start">
                            <a
                                href="{{ $targetUrl }}"
                                class="craft-btn inline-flex items-center gap-1 rounded-xl border border-[#b499ff]/30 bg-[#f3effe] px-3 py-1.5 text-xs font-bold text-[#382186] hover:bg-[#e7e2f7] hover:border-[#382186] transition-all shadow-2xs"
                                title="Lihat detail booking"
                            >
                                <span>Detail</span>
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>

                            @if ($isUnread)
                                <form action="{{ route('owner.notifications.read', $notification->id) }}" method="POST">
                                    @csrf
                                    <button
                                        type="submit"
                                        class="craft-btn rounded-xl p-1.5 text-[#6e6584] hover:bg-[#f7f7fa] hover:text-[#231a3d] transition cursor-pointer"
                                        title="Tandai sudah dibaca"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </button>
                                </form>
                            @endif

                            <form action="{{ route('owner.notifications.destroy', $notification->id) }}" method="POST" onsubmit="return confirm('Hapus notifikasi ini?');">
                                @csrf
                                @method('DELETE')
                                <button
                                    type="submit"
                                    class="craft-btn rounded-xl p-1.5 text-[#6e6584] hover:bg-rose-50 hover:text-rose-600 transition cursor-pointer"
                                    title="Hapus notifikasi"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-4">
            {{ $notifications->links() }}
        </div>
    @endif

</div>
@endsection
