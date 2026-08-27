<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Booking Dibatalkan</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #F3F4F6; font-family: 'Segoe UI', Arial, sans-serif; color: #111827; }
        .wrapper { max-width: 580px; margin: 32px auto; background: #fff; border-radius: 20px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,.06); }
        .header { background: linear-gradient(135deg, #DC2626 0%, #991B1B 100%); padding: 36px 40px; text-align: center; }
        .header-icon { display: inline-flex; align-items: center; justify-content: center; width: 64px; height: 64px; border-radius: 50%; background: rgba(255,255,255,.2); margin-bottom: 16px; }
        .header h1 { color: #fff; font-size: 22px; font-weight: 700; }
        .header p { color: rgba(255,255,255,.8); font-size: 14px; margin-top: 6px; }
        .body { padding: 36px 40px; }
        .booking-code { display: inline-block; background: #FEF2F2; color: #DC2626; font-size: 18px; font-weight: 700; letter-spacing: 0.05em; padding: 10px 20px; border-radius: 12px; margin-bottom: 24px; }
        .detail-card { background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 16px; padding: 24px; margin-bottom: 24px; }
        .detail-card h3 { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.12em; color: #6B7280; margin-bottom: 16px; }
        .detail-row { display: flex; justify-content: space-between; align-items: flex-start; padding: 8px 0; border-bottom: 1px solid #E5E7EB; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { font-size: 13px; color: #6B7280; }
        .detail-value { font-size: 13px; font-weight: 600; color: #111827; text-align: right; max-width: 60%; }
        .refund-card { background: #ECFDF5; border: 1px solid #6EE7B7; border-radius: 12px; padding: 16px 20px; margin-bottom: 24px; }
        .refund-card p { font-size: 13px; color: #065F46; line-height: 1.6; }
        .footer { background: #F9FAFB; border-top: 1px solid #E5E7EB; padding: 24px 40px; text-align: center; }
        .footer p { font-size: 12px; color: #9CA3AF; line-height: 1.6; }
        .footer a { color: #4F46E5; text-decoration: none; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <div class="header-icon">
                <svg width="32" height="32" fill="none" stroke="white" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </div>
            <h1>Booking Dibatalkan</h1>
            <p>Kami telah memproses pembatalan Anda</p>
        </div>

        <div class="body">
            <p style="font-size:14px; color:#374151; margin-bottom:20px;">
                Halo, <strong>{{ $booking->namapelanggan }}</strong>.<br/>
                Booking Anda di <strong>{{ $booking->tenant->namabisnis }}</strong> telah berhasil dibatalkan.
            </p>

            <div style="text-align:center; margin-bottom:24px;">
                <p style="font-size:12px; color:#6B7280; margin-bottom:8px;">KODE BOOKING</p>
                <span class="booking-code">{{ $booking->booking_code }}</span>
            </div>

            <div class="detail-card">
                <h3>Detail Booking yang Dibatalkan</h3>
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
                    <span class="detail-label">Total Pembayaran</span>
                    <span class="detail-value">{{ $priceLabel }}</span>
                </div>
            </div>

            @if($booking->refund)
            <div class="refund-card">
                <p>
                    <strong>💰 Informasi Refund:</strong><br/>
                    Refund sebesar <strong>{{ $booking->refund->jumlah_formatted }}</strong> sedang diproses. 
                    Dana akan dikembalikan ke metode pembayaran asal Anda dalam 3–14 hari kerja, 
                    tergantung kebijakan bank/e-wallet Anda.
                </p>
            </div>
            @endif

            <p style="font-size:13px; color:#6B7280; line-height:1.6;">
                Jika Anda memiliki pertanyaan tentang pembatalan ini, silakan hubungi 
                <strong>{{ $booking->tenant->namabisnis }}</strong> secara langsung.
            </p>
        </div>

        <div class="footer">
            <p>Email ini dikirim oleh <a href="#">BookQu</a> atas nama <strong>{{ $booking->tenant->namabisnis }}</strong>.</p>
        </div>
    </div>
</body>
</html>
