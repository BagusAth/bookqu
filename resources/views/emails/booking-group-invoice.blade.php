<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Konfirmasi Reservasi & Invoice Pembayaran</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #F3F4F6; font-family: 'Segoe UI', Arial, sans-serif; color: #111827; }
        .wrapper { max-width: 580px; margin: 32px auto; background: #fff; border-radius: 20px; overflow: hidden; box-shadow: 0 4px 24px rgba(79,70,229,.08); }
        .header { background: linear-gradient(135deg, #059669 0%, #047857 100%); padding: 36px 40px; text-align: center; }
        .header-icon { display: inline-flex; align-items: center; justify-content: center; width: 64px; height: 64px; border-radius: 50%; background: rgba(255,255,255,.2); margin-bottom: 16px; }
        .header h1 { color: #fff; font-size: 22px; font-weight: 700; }
        .header p { color: rgba(255,255,255,.8); font-size: 14px; margin-top: 6px; }
        .body { padding: 36px 40px; }
        .greeting { font-size: 16px; color: #374151; margin-bottom: 20px; }
        .order-badge { display: inline-block; background: #ECFDF5; color: #059669; font-size: 16px; font-weight: 700; letter-spacing: 0.05em; padding: 10px 20px; border-radius: 12px; margin-bottom: 24px; font-family: monospace; }
        .detail-card { background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 16px; padding: 24px; margin-bottom: 24px; }
        .detail-card h3 { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.12em; color: #6B7280; margin-bottom: 16px; }
        .detail-row { display: flex; justify-content: space-between; align-items: flex-start; padding: 8px 0; border-bottom: 1px solid #E5E7EB; font-size: 13px; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: #6B7280; }
        .detail-value { font-weight: 600; color: #111827; text-align: right; }
        .slot-item { background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 10px; padding: 10px 14px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center; font-size: 13px; }
        .total-card { background: linear-gradient(135deg, #ECFDF5 0%, #D1FAE5 100%); border: 2px solid #6EE7B7; border-radius: 16px; padding: 24px; margin-bottom: 24px; text-align: center; }
        .total-label { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.12em; color: #6B7280; margin-bottom: 8px; }
        .total-amount { font-size: 28px; font-weight: 800; color: #059669; }
        .cta-section { text-align: center; margin: 28px 0; }
        .cta-btn { display: inline-block; background: linear-gradient(135deg, #059669, #047857); color: #fff; font-size: 15px; font-weight: 700; padding: 14px 36px; border-radius: 14px; text-decoration: none; letter-spacing: 0.02em; }
        .note { background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 12px; padding: 16px 20px; font-size: 13px; color: #1E40AF; margin-bottom: 24px; }
        .footer { background: #F9FAFB; border-top: 1px solid #E5E7EB; padding: 24px 40px; text-align: center; }
        .footer p { font-size: 12px; color: #9CA3AF; line-height: 1.6; }
    </style>
</head>
<body>
    @php
        $firstBk = $bookings->first();
        $tenant = $payment->tenant;
        $layanan = $firstBk?->layanan;
        $durasi = (int) ($layanan?->durasi ?? 60);
        $tglFormatted = $firstBk?->tanggalbooking ? \Carbon\Carbon::parse($firstBk->tanggalbooking)->translatedFormat('l, d F Y') : '-';
    @endphp
    <div class="wrapper">
        <div class="header">
            <div class="header-icon">
                <svg width="32" height="32" fill="none" stroke="white" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <h1>Bukti Reservasi & Pembayaran 🧾</h1>
            <p>Pembayaran Anda telah berhasil dikonfirmasi</p>
        </div>

        <div class="body">
            <p class="greeting">Halo, <strong>{{ $firstBk?->namapelanggan }}</strong>!</p>
            <p style="font-size:14px; color:#6B7280; margin-bottom:20px;">
                Terima kasih telah melakukan reservasi di <strong>{{ $tenant?->namabisnis }}</strong>.
                Seluruh sesi reservasi Anda telah berhasil dikonfirmasi dalam satu grup pembayaran:
            </p>

            <div style="text-align:center; margin-bottom:24px;">
                <p style="font-size:12px; color:#6B7280; margin-bottom:8px;">ORDER ID RESERVASI</p>
                <span class="order-badge">{{ $orderId }}</span>
            </div>

            <div class="detail-card">
                <h3>Rincian Reservasi</h3>
                <div class="detail-row">
                    <span class="detail-label">Layanan</span>
                    <span class="detail-value">{{ $layanan?->namalayanan ?? 'Layanan' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Tanggal Pelaksanaan</span>
                    <span class="detail-value">{{ $tglFormatted }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Jumlah Sesi</span>
                    <span class="detail-value">{{ $bookings->count() }} Slot Waktu</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Metode Pembayaran</span>
                    <span class="detail-value">{{ $paymentMethod }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Waktu Transaksi</span>
                    <span class="detail-value">{{ $paymentDate }}</span>
                </div>
            </div>

            <div class="detail-card">
                <h3>Jadwal Sesi Terpilih</h3>
                @foreach($bookings as $idx => $bk)
                    @php
                        $bkDate = $bk->tanggalbooking instanceof \Carbon\CarbonInterface
                            ? $bk->tanggalbooking->format('Y-m-d')
                            : \Carbon\Carbon::parse($bk->tanggalbooking)->format('Y-m-d');
                        $start = \Carbon\Carbon::parse($bkDate . ' ' . $bk->jam);
                        $end = (clone $start)->addMinutes($durasi);
                    @endphp
                    <div class="slot-item">
                        <div>
                            <strong>Sesi #{{ $idx + 1 }}:</strong> {{ $start->format('H:i') }} – {{ $end->format('H:i') }} WIB
                        </div>
                        <div style="color:#6B7280; font-family:monospace; font-size:12px;">
                            {{ $bk->booking_code }}
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="total-card">
                <p class="total-label">TOTAL PEMBAYARAN</p>
                <p class="total-amount">{{ $totalAmountFormatted }}</p>
                <span style="display:inline-block; background:#D1FAE5; color:#065F46; font-size:12px; font-weight:700; padding:4px 12px; border-radius:20px; margin-top:8px;">LUNAS</span>
            </div>

            <div class="cta-section">
                <a href="{{ $manageUrl }}" class="cta-btn">
                    Kelola Reservasi Anda →
                </a>
                <p style="font-size:12px; color:#9CA3AF; margin-top:10px;">
                    Gunakan tautan aman di atas untuk melihat detail lengkap atau mencetak invoice.
                </p>
            </div>

            @if($bookings->count() > 1)
                <div class="note">
                    <strong>Informasi Reservasi Multi-Slot:</strong>
                    Sesi di atas merupakan satu kesatuan paket reservasi berurutan. Pembatalan atau perubahan jadwal tidak dapat dilakukan per slot individual.
                </div>
            @endif
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $tenant?->namabisnis }}. Didukung oleh BookQu Platform.</p>
        </div>
    </div>
</body>
</html>
