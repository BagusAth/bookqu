<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Invoice {{ $booking->booking_code }} — {{ $booking->tenant->namabisnis }}</title>
    <link rel="stylesheet" href="{{ asset('css/booking-manage.css') }}" />
    <style>
        /* Extra print overrides */
        @page { margin: 20mm; }
    </style>
</head>
<body class="invoice-page">

    {{-- ── Print / Back buttons (hidden on print) ── --}}
    <div class="no-print flex items-center justify-between px-6 py-4 border-b border-[#E5E7EB] bg-white">
        <a
            href="{{ route('booking.manage', ['booking_code' => $booking->booking_code, 'token' => $token]) }}"
            class="flex items-center gap-2 text-sm font-semibold text-[#6B7280] hover:text-[#111827] transition"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            Kembali
        </a>
        <button
            onclick="window.print()"
            class="flex items-center gap-2 text-sm font-bold text-[#4F46E5] hover:text-[#4338CA] transition px-4 py-2 border border-[#C7D2FE] rounded-xl hover:bg-[#EEF2FF]"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            Cetak / Download PDF
        </button>
    </div>

    <div class="invoice-body">

        {{-- ── Invoice Header ── --}}
        <div class="invoice-header-bar">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap;">
                <div>
                    <p style="font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:0.15em; color:rgba(255,255,255,0.7); margin-bottom:6px;">
                        INVOICE BOOKING
                    </p>
                    <h1 style="font-size:24px; font-weight:800; color:#fff; letter-spacing:0.02em;">
                        {{ $booking->booking_code }}
                    </h1>
                    <p style="font-size:13px; color:rgba(255,255,255,0.75); margin-top:6px;">
                        Diterbitkan: {{ ($invoiceDate ?? $booking->created_at)->format('d M Y, H:i') }} WIB
                    </p>
                </div>
                <div style="text-align:right;">
                    <div style="display:inline-block; background:rgba(255,255,255,0.15); border-radius:12px; padding:10px 18px;">
                        <p style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.1em; color:rgba(255,255,255,0.7); margin-bottom:4px;">Status</p>
                        @php
                            $statusLabels = [
                                'paid'      => '✓ LUNAS',
                                'pending'   => '⏳ PENDING',
                                'cancelled' => '✕ DIBATALKAN',
                                'completed' => '✓ SELESAI',
                            ];
                        @endphp
                        <p style="font-size:16px; font-weight:800; color:#fff;">{{ $statusLabels[$booking->status] ?? strtoupper($booking->status) }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Parties Row ── --}}
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-bottom:32px;">

            {{-- Billed To --}}
            <div style="background:#F9FAFB; border:1px solid #E5E7EB; border-radius:18px; padding:24px;">
                <p style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.12em; color:#9CA3AF; margin-bottom:12px;">TAGIHAN KEPADA</p>
                <p style="font-size:15px; font-weight:700; color:#111827;">{{ $booking->namapelanggan }}</p>
                <p style="font-size:13px; color:#6B7280; margin-top:4px;">{{ $booking->email }}</p>
                <p style="font-size:13px; color:#6B7280; margin-top:2px;">{{ $booking->nomorhp }}</p>
            </div>

            {{-- Billed From --}}
            <div style="background:#F9FAFB; border:1px solid #E5E7EB; border-radius:18px; padding:24px;">
                <p style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.12em; color:#9CA3AF; margin-bottom:12px;">DARI</p>
                <p style="font-size:15px; font-weight:700; color:#111827;">{{ $booking->tenant->namabisnis }}</p>
                @if($booking->tenant->jenisbisnis)
                <p style="font-size:13px; color:#6B7280; margin-top:4px;">{{ $booking->tenant->jenisbisnis }}</p>
                @endif
                @if($booking->tenant->alamat)
                <p style="font-size:13px; color:#6B7280; margin-top:2px;">{{ $booking->tenant->alamat }}</p>
                @endif
                @if($booking->tenant->nomorhp)
                <p style="font-size:13px; color:#6B7280; margin-top:2px;">{{ $booking->tenant->nomorhp }}</p>
                @endif
            </div>
        </div>

        {{-- ── Item Table ── --}}
        <div style="background:#fff; border:1px solid #E5E7EB; border-radius:18px; overflow:hidden; margin-bottom:24px;">
            <table class="invoice-table">
                <thead>
                    <tr>
                        <th style="width:40%;">Layanan</th>
                        <th style="width:25%;">Jadwal</th>
                        <th style="width:15%; text-align:center;">Qty</th>
                        <th style="width:20%; text-align:right;">Harga</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <p style="font-weight:600; color:#111827;">{{ $booking->layanan->namalayanan ?? '-' }}</p>
                            @if($booking->layanan)
                            <p style="font-size:12px; color:#9CA3AF; margin-top:2px;">
                                {{ $booking->layanan->durasi }} {{ $booking->layanan->satuan_durasi ?? 'menit' }}
                            </p>
                            @endif
                        </td>
                        <td>
                            <p style="font-weight:600; color:#111827; font-size:13px;">
                                {{ \Carbon\Carbon::parse($booking->tanggalbooking)->format('d M Y') }}
                            </p>
                            <p style="font-size:12px; color:#9CA3AF; margin-top:2px;">
                                {{ \Carbon\Carbon::parse($booking->jam)->format('H:i') }} WIB
                            </p>
                        </td>
                        <td style="text-align:center; font-weight:600; color:#111827;">1</td>
                        <td style="text-align:right; font-weight:700; color:#4F46E5; font-size:15px;">
                            {{ $booking->priceLabel }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- ── Totals ── --}}
        <div style="display:flex; justify-content:flex-end; margin-bottom:32px;">
            <div style="width:280px; background:#F9FAFB; border:1px solid #E5E7EB; border-radius:18px; padding:20px;">
                <div style="display:flex; justify-content:space-between; font-size:13px; padding:6px 0; border-bottom:1px solid #E5E7EB;">
                    <span style="color:#6B7280;">Subtotal</span>
                    <span style="font-weight:600; color:#111827;">{{ $booking->priceLabel }}</span>
                </div>
                <div style="display:flex; justify-content:space-between; font-size:13px; padding:6px 0; border-bottom:1px solid #E5E7EB;">
                    <span style="color:#6B7280;">Pajak</span>
                    <span style="font-weight:600; color:#4F46E5;">Termasuk</span>
                </div>
                <div style="display:flex; justify-content:space-between; padding:12px 0 0 0; margin-top:4px;">
                    <span style="font-weight:800; font-size:15px; color:#111827;">TOTAL</span>
                    <span style="font-weight:800; font-size:18px; color:#4F46E5;">{{ $booking->priceLabel }}</span>
                </div>
            </div>
        </div>

        {{-- ── Payment Info ── --}}
        @if($booking->payment)
        <div style="background:#F9FAFB; border:1px solid #E5E7EB; border-radius:18px; padding:24px; margin-bottom:32px;">
            <p style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.12em; color:#9CA3AF; margin-bottom:14px;">INFORMASI PEMBAYARAN</p>
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px;">
                <div>
                    <p style="font-size:12px; color:#6B7280; margin-bottom:3px;">Metode</p>
                    <p style="font-size:13px; font-weight:600; color:#111827; text-transform:capitalize;">
                        {{ $booking->payment->metode ?? 'Midtrans' }}
                    </p>
                </div>
                <div>
                    <p style="font-size:12px; color:#6B7280; margin-bottom:3px;">Order ID</p>
                    <p style="font-size:13px; font-weight:600; color:#111827; font-family:monospace;">
                        {{ $booking->payment->order_id ?? '-' }}
                    </p>
                </div>
                <div>
                    <p style="font-size:12px; color:#6B7280; margin-bottom:3px;">Status</p>
                    <p style="font-size:13px; font-weight:700; color:#059669; text-transform:uppercase;">
                        {{ $booking->payment->status === 'sukses' ? 'LUNAS' : strtoupper($booking->payment->status) }}
                    </p>
                </div>
            </div>
        </div>
        @endif

        {{-- ── Footer Note ── --}}
        <div style="border-top:2px dashed #E5E7EB; padding-top:24px; text-align:center;">
            <p style="font-size:12px; color:#9CA3AF; line-height:1.7;">
                Invoice ini diterbitkan secara otomatis oleh sistem <strong>BookQu</strong>.<br/>
                Kode booking <strong>{{ $booking->booking_code }}</strong> adalah bukti sah pembayaran Anda.<br/>
                Pertanyaan? Hubungi <strong>{{ $booking->tenant->namabisnis }}</strong> di {{ $booking->tenant->nomorhp ?? '' }}.
            </p>
            <div style="margin-top:16px; display:inline-block; background:#EEF2FF; border-radius:12px; padding:8px 20px;">
                <p style="font-size:13px; font-weight:700; color:#4F46E5; font-family:monospace; letter-spacing:0.05em;">
                    {{ $booking->booking_code }}
                </p>
            </div>
        </div>

    </div>
</body>
</html>
