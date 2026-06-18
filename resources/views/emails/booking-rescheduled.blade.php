<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Jadwal Booking Diubah</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #F3F4F6; font-family: 'Segoe UI', Arial, sans-serif; color: #111827; }
        .wrapper { max-width: 580px; margin: 32px auto; background: #fff; border-radius: 20px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,.06); }
        .header { background: linear-gradient(135deg, #2563EB 0%, #1D4ED8 100%); padding: 36px 40px; text-align: center; }
        .header-icon { display: inline-flex; align-items: center; justify-content: center; width: 64px; height: 64px; border-radius: 50%; background: rgba(255,255,255,.2); margin-bottom: 16px; }
        .header h1 { color: #fff; font-size: 22px; font-weight: 700; }
        .header p { color: rgba(255,255,255,.8); font-size: 14px; margin-top: 6px; }
        .body { padding: 36px 40px; }
        .booking-code { display: inline-block; background: #EFF6FF; color: #1D4ED8; font-size: 18px; font-weight: 700; letter-spacing: 0.05em; padding: 10px 20px; border-radius: 12px; }
        .schedule-compare { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin: 24px 0; }
        .schedule-box { border-radius: 16px; padding: 20px; }
        .schedule-box.old { background: #FEF2F2; border: 1px solid #FECACA; }
        .schedule-box.new { background: #ECFDF5; border: 1px solid #6EE7B7; }
        .schedule-box label { display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 10px; }
        .schedule-box.old label { color: #DC2626; }
        .schedule-box.new label { color: #059669; }
        .schedule-box p { font-size: 14px; font-weight: 600; color: #111827; line-height: 1.6; }
        .detail-card { background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 16px; padding: 24px; margin-bottom: 24px; }
        .detail-card h3 { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.12em; color: #6B7280; margin-bottom: 16px; }
        .detail-row { display: flex; justify-content: space-between; align-items: flex-start; padding: 8px 0; border-bottom: 1px solid #E5E7EB; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { font-size: 13px; color: #6B7280; }
        .detail-value { font-size: 13px; font-weight: 600; color: #111827; text-align: right; }
        .cta-btn { display: inline-block; background: linear-gradient(135deg, #2563EB, #1D4ED8); color: #fff; font-size: 15px; font-weight: 700; padding: 14px 36px; border-radius: 14px; text-decoration: none; }
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
                    <rect x="3" y="4" width="18" height="18" rx="4"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 2v4M16 2v4M3 10h18"/>
                </svg>
            </div>
            <h1>Jadwal Booking Diubah 📅</h1>
            <p>Reschedule Anda berhasil diproses</p>
        </div>

        <div class="body">
            <p style="font-size:14px; color:#374151; margin-bottom:20px;">
                Halo, <strong>{{ $booking->namapelanggan }}</strong>!<br/>
                Jadwal booking Anda di <strong>{{ $booking->tenant->namabisnis }}</strong> telah berhasil diubah.
            </p>

            <div style="text-align:center; margin-bottom:24px;">
                <p style="font-size:12px; color:#6B7280; margin-bottom:8px;">KODE BOOKING</p>
                <span class="booking-code">{{ $booking->booking_code }}</span>
            </div>

            <div class="schedule-compare">
                <div class="schedule-box old">
                    <label>❌ Jadwal Lama</label>
                    <p>
                        {{ $booking->rescheduled_from_date ? \Carbon\Carbon::parse($booking->rescheduled_from_date)->format('d M Y') : '-' }}<br/>
                        {{ $booking->rescheduled_from_time ? \Carbon\Carbon::parse($booking->rescheduled_from_time)->format('H:i') . ' WIB' : '-' }}
                    </p>
                </div>
                <div class="schedule-box new">
                    <label>✅ Jadwal Baru</label>
                    <p>
                        {{ \Carbon\Carbon::parse($booking->tanggalbooking)->format('d M Y') }}<br/>
                        {{ \Carbon\Carbon::parse($booking->jam)->format('H:i') }} WIB
                    </p>
                </div>
            </div>

            <div class="detail-card">
                <h3>Informasi Booking</h3>
                <div class="detail-row">
                    <span class="detail-label">Layanan</span>
                    <span class="detail-value">{{ $booking->layanan->namalayanan ?? '-' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Lokasi</span>
                    <span class="detail-value">{{ $booking->tenant->namabisnis }}</span>
                </div>
            </div>

            <div style="text-align:center; margin: 28px 0;">
                <a href="{{ $manageUrl }}" class="cta-btn">Kelola Booking Saya →</a>
            </div>
        </div>

        <div class="footer">
            <p>Email ini dikirim oleh <a href="#">BookQu</a> atas nama <strong>{{ $booking->tenant->namabisnis }}</strong>.</p>
        </div>
    </div>
</body>
</html>
