<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Invoice {{ $payment->order_id }} — {{ $tenant->namabisnis }}</title>
    <link rel="icon" type="image/png" href="{{ asset('logo_icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; padding: 0 !important; }
            .invoice-card { box-shadow: none !important; border: 1px solid #e2e8f0 !important; }
        }
    </style>
</head>
<body class="bg-slate-50 font-sans text-slate-900 antialiased min-h-screen py-8 px-4 sm:px-6">

    @php
        $firstBooking = $bookings->first();
        $bookingDate = $firstBooking?->tanggalbooking ? \Carbon\Carbon::parse($firstBooking->tanggalbooking)->translatedFormat('l, d F Y') : '-';
        $durasiMenit = (int) ($firstBooking?->layanan?->durasi ?? 60);
        $backManageUrl = route('booking.manage.payment', ['order_id' => $payment->order_id]) . ($token ? '?token=' . $token : '');
    @endphp

    {{-- Top Floating Navigation --}}
    <div class="max-w-2xl mx-auto mb-6 flex items-center justify-between no-print">
        <a href="{{ $backManageUrl }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-600 hover:text-indigo-600 transition-colors">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            Kembali ke Manajemen Reservasi
        </a>
        <button onclick="window.print()" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-indigo-700 transition-all">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            Cetak Invoice
        </button>
    </div>

    {{-- Invoice Card --}}
    <div class="invoice-card max-w-2xl mx-auto rounded-3xl border border-slate-200/80 bg-white p-6 sm:p-10 shadow-sm">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-8 border-b border-slate-100">
            <div>
                <span class="inline-block text-xs font-extrabold uppercase tracking-wider text-indigo-600 mb-1">Bukti Reservasi Resmi</span>
                <h1 class="text-2xl font-black text-slate-900">{{ $tenant->namabisnis }}</h1>
                <p class="text-xs text-slate-500 mt-0.5">{{ $tenant->alamat ?: 'Indonesia' }}</p>
                @if($tenant->nomorhp)
                    <p class="text-xs text-slate-500">Telp: {{ $tenant->nomorhp }}</p>
                @endif
            </div>
            <div class="sm:text-right">
                <span class="inline-flex items-center rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700 border border-emerald-200 mb-2">
                    LUNAS / SUKSES
                </span>
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Order ID</p>
                <p class="font-mono text-sm font-extrabold text-slate-900">{{ $payment->order_id }}</p>
                <p class="text-[11px] text-slate-400 mt-1">{{ ($payment->updated_at ?? $payment->created_at)->format('d M Y, H:i') }} WIB</p>
            </div>
        </div>

        {{-- Billed To & Service Info --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 py-6 border-b border-slate-100 text-xs">
            <div>
                <span class="font-bold text-slate-400 uppercase tracking-wider block mb-1.5 text-[11px]">Dipesan Oleh</span>
                <p class="font-bold text-slate-900 text-sm">{{ $firstBooking->namapelanggan }}</p>
                <p class="text-slate-600 font-mono mt-0.5">{{ $firstBooking->email }}</p>
                <p class="text-slate-600 font-mono">{{ $firstBooking->nomorhp }}</p>
            </div>
            <div>
                <span class="font-bold text-slate-400 uppercase tracking-wider block mb-1.5 text-[11px]">Informasi Layanan</span>
                <p class="font-bold text-slate-900 text-sm">{{ $firstBooking->layanan?->namalayanan ?? 'Layanan' }}</p>
                <p class="text-slate-600 mt-0.5">Tanggal: <span class="font-semibold text-slate-900">{{ $bookingDate }}</span></p>
                <p class="text-slate-600">Total Sesi: <span class="font-semibold text-slate-900">{{ $bookings->count() }} slot</span></p>
            </div>
        </div>

        {{-- Itemized Slots Table --}}
        <div class="py-6 border-b border-slate-100">
            <h2 class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-3">Rincian Sesi Reservasi</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 text-slate-400">
                            <th class="py-2 font-bold">No</th>
                            <th class="py-2 font-bold">Waktu Sesi</th>
                            <th class="py-2 font-bold">Kode Slot</th>
                            <th class="py-2 text-right font-bold">Harga</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($bookings as $idx => $bk)
                            @php
                                $s = \Carbon\Carbon::parse($bk->tanggalbooking . ' ' . $bk->jam);
                                $e = (clone $s)->addMinutes($durasiMenit);
                            @endphp
                            <tr>
                                <td class="py-2.5 font-bold text-slate-500">{{ $idx + 1 }}</td>
                                <td class="py-2.5 font-semibold text-slate-800 font-mono">
                                    {{ $s->format('H:i') }} – {{ $e->format('H:i') }} WIB
                                </td>
                                <td class="py-2.5 text-slate-500 font-mono text-[11px]">
                                    {{ $bk->booking_code }}
                                </td>
                                <td class="py-2.5 text-right font-bold text-slate-900">
                                    {{ $bk->priceLabel }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Totals --}}
        <div class="pt-6 space-y-2 text-xs">
            <div class="flex justify-between text-slate-600">
                <span>Subtotal ({{ $bookings->count() }} Sesi)</span>
                <span class="font-semibold">Rp {{ number_format((float) $payment->jumlah, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between text-slate-600">
                <span>Metode Pembayaran</span>
                <span class="font-semibold uppercase">{{ $payment->metode ?: 'Midtrans' }}</span>
            </div>
            <div class="flex justify-between items-center pt-3 border-t border-slate-200">
                <span class="text-sm font-black text-slate-900">Total Dibayar</span>
                <span class="text-lg font-black text-indigo-700">Rp {{ number_format((float) $payment->jumlah, 0, ',', '.') }}</span>
            </div>
        </div>

        {{-- Notice footer --}}
        <div class="mt-8 pt-6 border-t border-slate-100 text-center text-[11px] text-slate-400">
            <p>Terima kasih atas reservasi Anda di {{ $tenant->namabisnis }}.</p>
            <p class="mt-1">Invoice ini merupakan bukti transaksi yang sah dari sistem reservasi BookQu.</p>
        </div>
    </div>
</body>
</html>
