@extends('customer.layouts.booking-shell')

@section('title', 'Konfirmasi Booking & Invoice')
@section('current_step', 6)

@section('content')
<div class="mx-auto max-w-2xl">
    {{-- Success Hero Banner --}}
    <div class="text-center mb-8">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 mb-4 shadow-sm">
            <svg class="h-9 w-9" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
        </div>
        <h1 class="text-2xl sm:text-3xl font-black text-[#0F172A] tracking-tight">Booking Berhasil Dikonfirmasi!</h1>
        <p class="mt-2 text-sm text-[#64748B] max-w-md mx-auto">
            Terima kasih! Pembayaran Anda telah diterima dan sesi jadwal Anda sudah resmi terdaftar.
        </p>
    </div>

    {{-- Professional Receipt Card --}}
    <div class="booking-receipt-card rounded-2xl border border-[#E2E8F0] bg-white shadow-sm overflow-hidden mb-8">
        {{-- Receipt Header --}}
        <div class="bg-gradient-to-r from-[#4F46E5] to-[#6366F1] p-6 text-white flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-white/80">Tanda Terima Resmi</p>
                <h2 class="text-xl font-bold mt-0.5">{{ $tenant->namabisnis }}</h2>
            </div>
            <div class="sm:text-right">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/20 backdrop-blur-xs px-3 py-1 text-xs font-bold text-white border border-white/30">
                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                    {{ $payment->status === 'sukses' ? 'LUNAS' : strtoupper($payment->status) }}
                </span>
            </div>
        </div>

        {{-- Order Identifiers Bar --}}
        <div class="bg-[#F8FAFC] px-6 py-4 border-b border-[#E2E8F0] grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs sm:text-sm">
            <div>
                <span class="text-[#64748B] block text-[11px] font-semibold uppercase tracking-wider">Kode Booking</span>
                <span class="font-mono text-base font-bold text-[#4F46E5] tracking-wide">{{ $booking->booking_code ?? '-' }}</span>
            </div>
            <div class="sm:text-right">
                <span class="text-[#64748B] block text-[11px] font-semibold uppercase tracking-wider">Order ID</span>
                <span class="font-mono text-xs sm:text-sm font-semibold text-[#0F172A]">{{ $payment->order_id }}</span>
            </div>
        </div>

        {{-- Session Details --}}
        <div class="p-6 space-y-6">
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-[#94A3B8] mb-3">Rincian Layanan &amp; Jadwal</h3>
                <div class="rounded-xl border border-[#E2E8F0] bg-[#F8FAFC] p-4 space-y-3 text-xs sm:text-sm">
                    <div class="flex justify-between items-start">
                        <span class="text-[#64748B]">Layanan</span>
                        <span class="font-bold text-[#0F172A] text-right">{{ $booking->layanan->namalayanan ?? 'Layanan' }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-[#64748B]">Durasi</span>
                        <span class="font-semibold text-[#0F172A]">{{ $booking->layanan->durasi ?? 60 }} {{ $booking->layanan->satuan_durasi ?? 'menit' }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-[#64748B]">Hari &amp; Tanggal</span>
                        <span class="font-bold text-[#0F172A]">{{ \Carbon\Carbon::parse($booking->tanggalbooking)->translatedFormat('l, d F Y') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-[#64748B]">Jam Sesi</span>
                        <span class="font-bold text-[#4F46E5]">Pukul {{ $booking->jam }} WIB</span>
                    </div>
                    <div class="flex justify-between items-start pt-2 border-t border-[#E2E8F0]">
                        <span class="text-[#64748B]">Lokasi / Tempat</span>
                        <span class="font-medium text-[#0F172A] text-right">{{ $tenant->namabisnis }}</span>
                    </div>
                </div>
            </div>

            {{-- Customer Details --}}
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-[#94A3B8] mb-3">Data Pemesan</h3>
                <div class="rounded-xl border border-[#E2E8F0] bg-[#F8FAFC] p-4 space-y-2 text-xs sm:text-sm">
                    <div class="flex justify-between">
                        <span class="text-[#64748B]">Nama Pemesan</span>
                        <span class="font-bold text-[#0F172A]">{{ $booking->namapelanggan }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-[#64748B]">Nomor WhatsApp</span>
                        <span class="font-medium text-[#0F172A]">{{ $booking->nomorhp }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-[#64748B]">Email</span>
                        <span class="font-medium text-[#0F172A]">{{ $booking->email }}</span>
                    </div>
                </div>
            </div>

            {{-- Payment Summary --}}
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-[#94A3B8] mb-3">Rincian Pembayaran</h3>
                <div class="rounded-xl border border-[#E2E8F0] bg-[#F8FAFC] p-4 space-y-2.5 text-xs sm:text-sm">
                    <div class="flex justify-between">
                        <span class="text-[#64748B]">Metode Pembayaran</span>
                        <span class="font-bold text-[#0F172A] uppercase">{{ $payment->metode ?? 'Midtrans' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-[#64748B]">Status Transaksi</span>
                        <span class="font-semibold text-emerald-700">Berhasil Dikonfirmasi</span>
                    </div>
                    <div class="flex justify-between items-center pt-3 border-t border-[#E2E8F0]">
                        <span class="text-sm font-bold text-[#0F172A]">Total Pembayaran</span>
                        <span class="text-lg font-black text-[#4F46E5]">Rp {{ number_format($payment->jumlah, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            {{-- Self-Service Manage Booking Box --}}
            @if ($booking->booking_code && $booking->cancellation_token)
                <div class="rounded-xl border border-[#C7D2FE] bg-[#EEF2FF]/60 p-4 text-xs">
                    <div class="flex items-start gap-2.5">
                        <svg class="h-4 w-4 text-[#4F46E5] shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <p class="font-bold text-[#4F46E5]">Kelola Booking Mandiri (Tanpa Perlu Login)</p>
                            <p class="text-[#64748B] mt-0.5 leading-relaxed">
                                Anda dapat melihat detail, membatalkan, atau mengubah jadwal booking ini kapan saja melalui tautan berikut:
                            </p>
                            <a
                                href="{{ route('booking.manage', ['booking_code' => $booking->booking_code]) . '?token=' . $booking->cancellation_token }}"
                                class="mt-2 inline-flex items-center gap-1 font-bold text-[#4F46E5] hover:underline break-all"
                            >
                                <span>{{ url('/manage/' . $booking->booking_code) }}</span>
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="no-print flex flex-col sm:flex-row items-center justify-center gap-3">
        <button
            onclick="window.print()"
            type="button"
            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl border border-[#CBD5E1] bg-white px-5 py-3 text-sm font-bold text-[#0F172A] shadow-xs hover:bg-[#F8FAFC] transition active:scale-98 cursor-pointer"
        >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
            </svg>
            <span>Cetak / Simpan Invoice</span>
        </button>

        <a
            href="{{ route('customer.booking.program', $tenant->slug) }}"
            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-[#4F46E5] hover:bg-[#4338CA] px-6 py-3 text-sm font-bold text-white shadow-md shadow-[#4F46E5]/20 transition active:scale-98"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            <span>Pesan Sesi Baru</span>
        </a>

        <a
            href="{{ url('/' . $tenant->slug) }}"
            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl border border-transparent px-4 py-3 text-sm font-semibold text-[#64748B] hover:text-[#0F172A] hover:bg-[#F1F5F9] transition"
        >
            <span>Kembali ke Beranda</span>
        </a>
    </div>
</div>
@endsection
