<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="Kelola booking Anda di {{ $booking->tenant->namabisnis }} — lihat detail, batalkan, atau reschedule tanpa login." />
    <title>{{ $booking->booking_code }} — Kelola Booking | {{ $booking->tenant->namabisnis }}</title>
    @vite('resources/css/app.css')
    <link rel="stylesheet" href="{{ asset('css/booking-program.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/booking-manage.css') }}" />
</head>
<body class="manage-page" x-data="manageBooking()" x-init="init()">

    {{-- ── Header ── --}}
    <header class="manage-header">
        <nav class="manage-shell flex items-center justify-between px-6 py-4">
            <a href="/" class="flex items-center gap-2">
                <img src="{{ asset('images/logo.png') }}" alt="BookQu Logo" class="h-8 w-auto" />
            </a>
            <div class="flex items-center gap-3">
                <a href="{{ $booking->tenant->slug ? url('/' . $booking->tenant->slug) : '/' }}"
                   class="text-sm font-semibold text-[#6B7280] hover:text-[#111827] transition">
                    ← Kembali
                </a>
            </div>
        </nav>
    </header>

    {{-- ── Flash messages ── --}}
    @if(session('success'))
    <div class="manage-shell mx-auto px-6 pt-6">
        <div class="alert-success">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    </div>
    @endif

    @if($errors->any())
    <div class="manage-shell mx-auto px-6 pt-6">
        <div class="alert-error">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>{{ $errors->first() }}</span>
        </div>
    </div>
    @endif

    <main class="manage-shell mx-auto px-6 py-8 space-y-6">

        {{-- ── Booking Code Header Card ── --}}
        <div class="manage-card p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="section-title">Kode Booking</p>
                    <div class="booking-code-display mt-2">
                        <span class="booking-code-text">{{ $booking->booking_code }}</span>
                        <button
                            type="button"
                            onclick="navigator.clipboard.writeText('{{ $booking->booking_code }}').then(() => this.textContent = '✓ Disalin').catch(()=>{}); setTimeout(() => this.textContent = 'Salin', 2000)"
                            class="text-xs font-semibold text-[#4F46E5] hover:text-[#4338CA] transition"
                        >Salin</button>
                    </div>
                </div>
                <div class="flex flex-col items-start sm:items-end gap-2">
                    @php
                        $statusConfig = [
                            'paid'      => ['class' => 'status-badge--paid',      'label' => '✓ Terkonfirmasi'],
                            'pending'   => ['class' => 'status-badge--pending',   'label' => '⏳ Menunggu Bayar'],
                            'cancelled' => ['class' => 'status-badge--cancelled', 'label' => '✕ Dibatalkan'],
                            'completed' => ['class' => 'status-badge--completed', 'label' => '✓ Selesai'],
                        ];
                        $sc = $statusConfig[$booking->status] ?? ['class' => 'status-badge--pending', 'label' => $booking->status];
                    @endphp
                    <span class="status-badge {{ $sc['class'] }}">{{ $sc['label'] }}</span>
                    <span class="text-xs text-[#9CA3AF]">Dibuat {{ $booking->created_at->diffForHumans() }}</span>
                </div>
            </div>
        </div>

        {{-- ── Two Column Layout ── --}}
        <div class="grid gap-6 lg:grid-cols-[1fr_300px]">

            {{-- ── Left: Booking Info + Timeline ── --}}
            <div class="space-y-6">

                {{-- Booking Details Card --}}
                <div class="manage-card p-6">
                    <p class="section-title">Detail Booking</p>
                    <div class="mt-4 space-y-0">
                        <div class="detail-row">
                            <span class="detail-label">Layanan</span>
                            <span class="detail-value">{{ $booking->layanan->namalayanan ?? '-' }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Tanggal</span>
                            <span class="detail-value">{{ \Carbon\Carbon::parse($booking->tanggalbooking)->translatedFormat('l, d F Y') }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Jam</span>
                            <span class="detail-value">{{ \Carbon\Carbon::parse($booking->jam)->format('H:i') }} WIB</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Durasi</span>
                            <span class="detail-value">{{ $booking->layanan->durasi ?? '-' }} {{ $booking->layanan->satuan_durasi ?? 'menit' }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Nama Pelanggan</span>
                            <span class="detail-value">{{ $booking->namapelanggan }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Email</span>
                            <span class="detail-value">{{ $booking->email }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">No. HP</span>
                            <span class="detail-value">{{ $booking->nomorhp }}</span>
                        </div>
                        @if($booking->catatan)
                        <div class="detail-row">
                            <span class="detail-label">Catatan</span>
                            <span class="detail-value max-w-xs">{{ $booking->catatan }}</span>
                        </div>
                        @endif
                        <div class="detail-row">
                            <span class="detail-label">Total Bayar</span>
                            <span class="detail-value text-[#4F46E5] text-base font-bold">{{ $booking->priceLabel }}</span>
                        </div>
                    </div>
                </div>

                {{-- Reschedule history --}}
                @if($booking->rescheduled_from_date)
                <div class="alert-info">
                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span>
                        <strong>Jadwal telah diubah.</strong> Jadwal asal:
                        {{ \Carbon\Carbon::parse($booking->rescheduled_from_date)->format('d M Y') }}
                        pukul {{ \Carbon\Carbon::parse($booking->rescheduled_from_time)->format('H:i') }} WIB.
                    </span>
                </div>
                @endif

                {{-- ── Activity Timeline ── --}}
                <div class="manage-card p-6">
                    <p class="section-title">Riwayat Aktivitas</p>
                    <div class="timeline mt-4">
                        @forelse($booking->logs as $log)
                        @php
                            $colorMap = [
                                'created'         => 'indigo',
                                'payment_pending' => 'amber',
                                'payment_success' => 'emerald',
                                'payment_failed'  => 'red',
                                'cancelled'       => 'red',
                                'rescheduled'     => 'blue',
                                'viewed'          => 'gray',
                            ];
                            $dotColor = $colorMap[$log->event] ?? 'gray';

                            $iconMap = [
                                'created'         => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>',
                                'payment_pending' => '<circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7v5l3 3"/>',
                                'payment_success' => '<path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>',
                                'payment_failed'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>',
                                'cancelled'       => '<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>',
                                'rescheduled'     => '<rect x="3" y="4" width="18" height="18" rx="4"/><path stroke-linecap="round" d="M8 2v4M16 2v4M3 10h18"/>',
                                'viewed'          => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>',
                            ];
                            $icon = $iconMap[$log->event] ?? '<circle cx="12" cy="12" r="9"/>';
                        @endphp
                        <div class="timeline-item">
                            <div class="timeline-line"></div>
                            <div class="timeline-dot timeline-dot--{{ $dotColor }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    {!! $icon !!}
                                </svg>
                            </div>
                            <div class="timeline-content">
                                <time>{{ $log->created_at->format('d M Y, H:i') }} WIB</time>
                                <p class="font-semibold text-[#111827]">{{ $log->event_label }}</p>
                                @if($log->description)
                                    <p class="mt-0.5">{{ $log->description }}</p>
                                @endif
                            </div>
                        </div>
                        @empty
                        <p class="text-sm text-[#9CA3AF] py-2">Belum ada aktivitas yang tercatat.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- ── Right: Actions + Tenant Info ── --}}
            <div class="space-y-6">

                {{-- Actions Card --}}
                <div class="manage-card p-6">
                    <p class="section-title">Kelola Booking</p>
                    <div class="mt-4 space-y-3">

                        {{-- Download Invoice --}}
                        <a
                            href="{{ route('booking.manage.invoice', ['booking_code' => $booking->booking_code, 'token' => $token]) }}"
                            target="_blank"
                            class="btn-secondary w-full"
                            id="btn-invoice"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Download Invoice
                        </a>

                        @if($booking->status === 'paid')
                            {{-- Reschedule --}}
                            @if($canReschedule)
                            <a
                                href="{{ route('booking.manage.reschedule.show', ['booking_code' => $booking->booking_code, 'token' => $token]) }}"
                                class="btn-secondary w-full"
                                id="btn-reschedule"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                Reschedule Jadwal
                            </a>
                            @else
                            <div class="tooltip-wrapper relative" x-data="{ tip: false }">
                                <button type="button" class="btn-secondary btn-disabled w-full" @mouseenter="tip=true" @mouseleave="tip=false">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    Reschedule Jadwal
                                </button>
                                <div x-show="tip" x-transition class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-2 bg-[#111827] text-white text-xs rounded-xl whitespace-nowrap z-10" x-cloak>
                                    Batas reschedule: {{ $booking->tenant->reschedule_before_hours ?? 24 }} jam sebelum jadwal
                                </div>
                            </div>
                            @endif

                            {{-- Cancel --}}
                            @if($canCancel)
                            <button
                                type="button"
                                class="btn-danger w-full"
                                id="btn-cancel"
                                @click="showCancelModal = true"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Batalkan Booking
                            </button>
                            @else
                            <div class="relative" x-data="{ tip: false }">
                                <button type="button" class="btn-danger btn-disabled w-full" @mouseenter="tip=true" @mouseleave="tip=false">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                    Batalkan Booking
                                </button>
                                <div x-show="tip" x-transition class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-2 bg-[#111827] text-white text-xs rounded-xl whitespace-nowrap z-10" x-cloak>
                                    Batas pembatalan: {{ $booking->tenant->cancel_before_hours ?? 24 }} jam sebelum jadwal
                                </div>
                            </div>
                            @endif

                            {{-- Cancel deadline notice --}}
                            @if($cancelDeadline)
                            <p class="text-xs text-center text-[#9CA3AF]">
                                Batas batalkan: {{ $cancelDeadline->format('d M Y, H:i') }} WIB
                            </p>
                            @endif

                        @elseif($booking->status === 'cancelled')
                        <div class="alert-error">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            <span>Booking ini telah dibatalkan.</span>
                        </div>
                        @elseif($booking->status === 'completed')
                        <div class="alert-success">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>Booking ini telah selesai. Terima kasih!</span>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Tenant Info Card --}}
                <div class="manage-card p-6">
                    <p class="section-title">Informasi Bisnis</p>
                    <div class="mt-4 space-y-3">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#EEF2FF] text-[#4F46E5] flex-shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 21V8l9-5 9 5v13M3 21h18"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-semibold text-[#111827] text-sm">{{ $booking->tenant->namabisnis }}</p>
                                <p class="text-xs text-[#6B7280]">{{ $booking->tenant->jenisbisnis ?? '' }}</p>
                            </div>
                        </div>
                        @if($booking->tenant->alamat)
                        <div class="flex items-start gap-3">
                            <svg class="w-4 h-4 text-[#9CA3AF] flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span class="text-xs text-[#6B7280]">{{ $booking->tenant->alamat }}</span>
                        </div>
                        @endif
                        @if($booking->tenant->nomorhp)
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 text-[#9CA3AF] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            <a href="tel:{{ $booking->tenant->nomorhp }}" class="text-xs text-[#4F46E5] font-medium">{{ $booking->tenant->nomorhp }}</a>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Security notice --}}
                <div class="alert-warning">
                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <p class="text-xs leading-relaxed">
                        <strong>Jaga link ini!</strong> URL halaman ini bersifat rahasia dan memberikan akses untuk mengelola booking Anda. Jangan bagikan kepada siapapun.
                    </p>
                </div>

            </div>
        </div>
    </main>

    {{-- ── Cancel Confirmation Modal ── --}}
    <div
        class="modal-overlay"
        x-show="showCancelModal"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @keydown.escape.window="showCancelModal = false"
    >
        <div
            class="modal-box"
            @click.stop
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
        >
            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#FEF2F2] text-[#DC2626] mx-auto mb-6">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
            </div>

            <h2 class="text-xl font-bold text-[#111827] text-center">Batalkan Booking?</h2>
            <p class="mt-2 text-sm text-center text-[#6B7280]">
                Tindakan ini tidak dapat diundur. Booking <strong>{{ $booking->booking_code }}</strong> akan dibatalkan dan proses refund akan diinisiasi.
            </p>

            <div class="mt-6 bg-[#F9FAFB] rounded-2xl p-4 text-sm space-y-2">
                <div class="flex justify-between">
                    <span class="text-[#6B7280]">Layanan</span>
                    <span class="font-medium">{{ $booking->layanan->namalayanan ?? '-' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-[#6B7280]">Tanggal</span>
                    <span class="font-medium">{{ \Carbon\Carbon::parse($booking->tanggalbooking)->format('d M Y') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-[#6B7280]">Total Refund</span>
                    <span class="font-bold text-[#059669]">{{ $booking->priceLabel }}</span>
                </div>
            </div>

            <form
                method="POST"
                action="{{ route('booking.manage.cancel', ['booking_code' => $booking->booking_code, 'token' => $token]) }}"
                class="mt-6 space-y-3"
            >
                @csrf
                <button
                    type="submit"
                    class="btn-danger w-full"
                    :disabled="isCancelling"
                    @click="isCancelling = true"
                >
                    <span x-show="!isCancelling">Ya, Batalkan Booking</span>
                    <span x-show="isCancelling" class="flex items-center gap-2" x-cloak>
                        <span class="spinner"></span> Memproses...
                    </span>
                </button>
                <button
                    type="button"
                    class="btn-secondary w-full"
                    @click="showCancelModal = false"
                    :disabled="isCancelling"
                >
                    Tidak, Kembali
                </button>
            </form>
        </div>
    </div>

    <footer class="border-t border-[#E5E7EB] bg-[#EDEBFA] mt-12">
        <div class="manage-shell mx-auto px-6 py-8 text-center">
            <p class="text-xs text-[#9CA3AF]">
                &copy; {{ date('Y') }} BookQu. Halaman ini aman dan diakses melalui link unik yang dikirimkan ke email Anda.
            </p>
        </div>
    </footer>

    <script>
        function manageBooking() {
            return {
                showCancelModal: false,
                isCancelling:    false,
                init() {
                    // Auto-show cancel modal if redirected with cancel error
                    @if($errors->has('cancel'))
                    this.showCancelModal = true;
                    @endif
                },
            };
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
