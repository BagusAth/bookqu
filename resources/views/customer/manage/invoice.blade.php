<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Invoice {{ $booking->booking_code }} — {{ $booking->tenant->namabisnis }}</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Vite CSS -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- html2canvas for instant image download -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <style>
        :root {
            --bq-primary: #4F46E5;
            --bq-primary-hover: #4338CA;
            --bq-primary-light: #EEF2FF;
            --bq-primary-border: #C7D2FE;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: #F8FAFC;
            color: #0F172A;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }

        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }

        /* ── Screen Actions Bar ── */
        .action-bar {
            position: sticky;
            top: 0;
            z-index: 50;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid #E2E8F0;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.15s ease-in-out;
            text-decoration: none;
            line-height: 1.25;
        }

        .btn-secondary {
            background: #FFFFFF;
            color: #475569;
            border: 1px solid #CBD5E1;
        }
        .btn-secondary:hover {
            background: #F1F5F9;
            color: #0F172A;
            border-color: #94A3B8;
        }

        .btn-primary {
            background: #4F46E5;
            color: #FFFFFF;
            border: 1px solid #4F46E5;
            box-shadow: 0 1px 2px 0 rgba(79, 70, 229, 0.2);
        }
        .btn-primary:hover {
            background: #4338CA;
            border-color: #4338CA;
        }

        .btn-wa {
            background: #ECFDF5;
            color: #047857;
            border: 1px solid #A7F3D0;
        }
        .btn-wa:hover {
            background: #D1FAE5;
            color: #065F46;
        }

        /* ── Document Container ── */
        .invoice-wrapper {
            max-width: 860px;
            margin: 28px auto 48px auto;
            padding: 0 16px;
        }

        .invoice-sheet {
            background: #FFFFFF;
            border-radius: 20px;
            border: 1px solid #E2E8F0;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.08), 0 8px 10px -6px rgba(15, 23, 42, 0.04);
            overflow: hidden;
        }

        /* ── Header Banner ── */
        .invoice-header {
            background: linear-gradient(135deg, #4F46E5 0%, #4338CA 55%, #3730A3 100%);
            padding: 32px 36px;
            color: #FFFFFF;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 24px;
            flex-wrap: wrap;
        }

        .tenant-brand {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .tenant-logo-box {
            width: 56px;
            height: 56px;
            background: #FFFFFF;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            flex-shrink: 0;
        }

        .tenant-logo-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .tenant-avatar-fallback {
            width: 56px;
            height: 56px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 800;
            color: #FFFFFF;
            flex-shrink: 0;
        }

        /* ── Content Body ── */
        .invoice-content {
            padding: 36px;
        }

        .party-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 28px;
        }

        @media (max-width: 640px) {
            .party-grid {
                grid-template-columns: 1fr;
            }
            .invoice-header {
                padding: 24px;
            }
            .invoice-content {
                padding: 24px;
            }
        }

        .party-card {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 14px;
            padding: 20px;
        }

        .party-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748B;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .table-container {
            border: 1px solid #E2E8F0;
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 28px;
        }

        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 14px;
        }

        .invoice-table th {
            background: #F8FAFC;
            padding: 14px 18px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #475569;
            border-bottom: 1px solid #E2E8F0;
        }

        .invoice-table td {
            padding: 16px 18px;
            border-bottom: 1px solid #F1F5F9;
            vertical-align: middle;
        }

        .invoice-table tr:last-child td {
            border-bottom: none;
        }

        .totals-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 28px;
        }

        .totals-box {
            width: 320px;
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 14px;
            padding: 18px 20px;
        }

        .totals-row {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            color: #64748B;
            padding: 6px 0;
            border-bottom: 1px solid #E2E8F0;
        }

        .totals-row.grand-total {
            border-bottom: none;
            margin-top: 6px;
            padding-top: 10px;
            font-size: 16px;
            font-weight: 800;
            color: #0F172A;
            align-items: baseline;
        }

        .grand-total .amount {
            font-size: 20px;
            color: #4F46E5;
        }

        .payment-info-box {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 28px;
        }

        .payment-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-top: 12px;
        }

        @media (max-width: 640px) {
            .payment-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        .footer-seal {
            border-top: 2px dashed #CBD5E1;
            padding-top: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
        }

        /* ── Print Media Styles ── */
        @media print {
            @page {
                size: A4 portrait;
                margin: 10mm 12mm 10mm 12mm;
            }

            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            body {
                background: #FFFFFF !important;
                color: #0F172A !important;
                padding: 0 !important;
                margin: 0 !important;
                font-size: 11pt !important;
            }

            .no-print {
                display: none !important;
            }

            .invoice-wrapper {
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .invoice-sheet {
                border: 1px solid #CBD5E1 !important;
                box-shadow: none !important;
                border-radius: 0 !important;
            }

            .invoice-header {
                background: #4F46E5 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                padding: 24px 28px !important;
            }

            .invoice-content {
                padding: 24px 28px !important;
            }

            .party-card,
            .totals-box,
            .payment-info-box {
                background: #F8FAFC !important;
                border-color: #CBD5E1 !important;
                page-break-inside: avoid;
                break-inside: avoid;
            }

            .table-container {
                border-color: #CBD5E1 !important;
                page-break-inside: avoid;
                break-inside: avoid;
            }

            .footer-seal {
                page-break-inside: avoid;
                break-inside: avoid;
            }
        }
    </style>
</head>
<body>

    @php
        \Carbon\Carbon::setLocale('id');
        $issuedAt = \Carbon\Carbon::parse($invoiceDate ?? $booking->created_at);
        $bookingDate = \Carbon\Carbon::parse($booking->tanggalbooking);
        $jamFormatted = \Carbon\Carbon::parse($booking->jam)->format('H:i');

        $statusConfig = [
            'paid' => [
                'label' => 'LUNAS / BERHASIL',
                'bg'    => 'bg-emerald-500/20 text-emerald-300 border-emerald-400/40',
                'badge' => '✓ LUNAS',
            ],
            'pending' => [
                'label' => 'MENUNGGU PEMBAYARAN',
                'bg'    => 'bg-amber-500/20 text-amber-300 border-amber-400/40',
                'badge' => '⏳ PENDING',
            ],
            'completed' => [
                'label' => 'SELESAI',
                'bg'    => 'bg-indigo-500/20 text-indigo-200 border-indigo-400/40',
                'badge' => '✓ SELESAI',
            ],
            'cancelled' => [
                'label' => 'DIBATALKAN',
                'bg'    => 'bg-rose-500/20 text-rose-300 border-rose-400/40',
                'badge' => '✕ DIBATALKAN',
            ],
        ];
        $currentStatus = $statusConfig[$booking->status] ?? [
            'label' => strtoupper($booking->status),
            'bg'    => 'bg-white/20 text-white border-white/30',
            'badge' => strtoupper($booking->status),
        ];

        // Back URL to manage booking
        $backUrl = route('booking.manage', ['booking_code' => $booking->booking_code]) . ($token ? '?token=' . $token : '');

        // WhatsApp Share URL
        $waShareText = "Halo! Berikut invoice resmi reservasi *" . ($booking->layanan->namalayanan ?? 'Layanan') . "* di *" . $booking->tenant->namabisnis . "*\n"
            . "🔖 Kode Booking: *" . $booking->booking_code . "*\n"
            . "📅 Tanggal: " . $bookingDate->translatedFormat('l, d F Y') . " (" . $jamFormatted . " WIB)\n"
            . "💰 Total: *" . $booking->priceLabel . "* (" . $currentStatus['badge'] . ")\n"
            . "Lihat Invoice: " . url()->full();
        $waShareUrl = 'https://api.whatsapp.com/send?text=' . urlencode($waShareText);
    @endphp

    {{-- ── SCREEN ACTION TOOLBAR (Hidden when printed) ── --}}
    <header class="action-bar no-print">
        <div class="flex items-center gap-3">
            <a href="{{ $backUrl }}" class="btn-action btn-secondary" title="Kembali ke halaman kelola booking">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                </svg>
                <span>Kembali</span>
            </a>
            <span class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-100 border border-slate-200 text-xs font-semibold text-slate-600">
                <svg class="w-3.5 h-3.5 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                Dokumen Resmi BookQu
            </span>
        </div>

        <div class="flex items-center gap-2">
            {{-- WhatsApp Share --}}
            <a href="{{ $waShareUrl }}" target="_blank" rel="noopener noreferrer" class="btn-action btn-wa hidden sm:inline-flex" title="Bagikan Invoice ke WhatsApp">
                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                    <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.664-.698c.969.54 1.761.817 2.796.817 3.182 0 5.768-2.587 5.768-5.766.001-3.181-2.585-5.766-5.768-5.766zm0 10.42c-.93 0-1.636-.26-2.527-.79l-.18-.108-1.58.414.421-1.54-.118-.188c-.604-.962-.976-1.745-.976-2.61 0-2.678 2.181-4.857 4.86-4.857 2.677 0 4.857 2.18 4.857 4.858 0 2.677-2.18 4.857-4.857 4.857z"/>
                </svg>
                <span>Bagikan WA</span>
            </a>

            {{-- Save as Image (PNG) --}}
            <button type="button" id="btn-save-image" onclick="saveInvoiceAsImage()" class="btn-action btn-secondary" title="Download invoice format gambar PNG">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span>Simpan Gambar</span>
            </button>

            {{-- Print / Save PDF --}}
            <button type="button" onclick="window.print()" class="btn-action btn-primary" title="Cetak atau simpan sebagai PDF">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                <span>Cetak / Unduh PDF</span>
            </button>
        </div>
    </header>

    {{-- ── INVOICE SHEET CONTAINER ── --}}
    <main class="invoice-wrapper">
        <div class="invoice-sheet" id="invoice-card">

            {{-- ── HEADER BANNER ── --}}
            <div class="invoice-header">
                {{-- Merchant Brand Info --}}
                <div class="tenant-brand">
                    @if($booking->tenant->logo_url)
                        <div class="tenant-logo-box">
                            <img
                                src="{{ $booking->tenant->logo_url }}"
                                alt="{{ $booking->tenant->namabisnis }}"
                                onerror="this.parentElement.style.display='none'; document.getElementById('logo-fallback').style.display='flex';"
                            />
                        </div>
                        <div id="logo-fallback" class="tenant-avatar-fallback" style="display: none;">
                            {{ strtoupper(substr($booking->tenant->namabisnis, 0, 1)) }}
                        </div>
                    @else
                        <div class="tenant-avatar-fallback">
                            {{ strtoupper(substr($booking->tenant->namabisnis, 0, 1)) }}
                        </div>
                    @endif

                    <div>
                        <div class="flex items-center gap-2">
                            <h1 style="font-size: 22px; font-weight: 800; margin: 0; color: #FFFFFF; letter-spacing: -0.02em;">
                                {{ $booking->tenant->namabisnis }}
                            </h1>
                        </div>
                        @if($booking->tenant->jenisbisnis)
                            <p style="font-size: 13px; color: rgba(255, 255, 255, 0.85); margin: 3px 0 0 0; font-weight: 500;">
                                {{ $booking->tenant->jenisbisnis }}
                            </p>
                        @endif
                        @if($booking->tenant->alamat)
                            <p style="font-size: 12px; color: rgba(255, 255, 255, 0.75); margin: 3px 0 0 0; max-width: 380px; line-height: 1.4;">
                                {{ $booking->tenant->alamat }}
                            </p>
                        @endif
                    </div>
                </div>

                {{-- Invoice Meta (Code, Date, Status) --}}
                <div style="text-align: right; min-width: 220px;">
                    <div style="display: inline-block; padding: 4px 10px; background: rgba(255, 255, 255, 0.15); border: 1px solid rgba(255, 255, 255, 0.25); border-radius: 6px; font-size: 11px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 8px;">
                        INVOICE RESMI
                    </div>
                    <div class="font-mono" style="font-size: 20px; font-weight: 800; color: #FFFFFF; letter-spacing: 0.04em;">
                        {{ $booking->booking_code }}
                    </div>
                    <p style="font-size: 12px; color: rgba(255, 255, 255, 0.8); margin: 4px 0 0 0;">
                        Diterbitkan: <span style="font-weight: 600; color: #FFFFFF;">{{ $issuedAt->translatedFormat('d M Y, H:i') }} WIB</span>
                    </p>

                    <div style="margin-top: 12px;">
                        <span style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 9999px; font-size: 12px; font-weight: 800; letter-spacing: 0.05em;" class="{{ $currentStatus['bg'] }} border">
                            @if($booking->status === 'paid')
                                <svg style="width: 14px; height: 14px;" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            @endif
                            {{ $currentStatus['label'] }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- ── INVOICE CONTENT BODY ── --}}
            <div class="invoice-content">

                {{-- Parties Row --}}
                <div class="party-grid">
                    {{-- Billed To --}}
                    <div class="party-card">
                        <div class="party-title">
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <span>DITAGIHKAN KEPADA (PELANGGAN)</span>
                        </div>
                        <p style="font-size: 16px; font-weight: 700; color: #0F172A; margin: 0 0 4px 0;">
                            {{ $booking->namapelanggan }}
                        </p>
                        <div style="font-size: 13px; color: #475569; display: flex; flex-direction: column; gap: 3px;">
                            <div class="flex items-center gap-2">
                                <span class="text-slate-400 text-xs">WhatsApp:</span>
                                <span class="font-medium text-slate-800">{{ $booking->nomorhp }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-slate-400 text-xs">Email:</span>
                                <span class="font-medium text-slate-800">{{ $booking->email }}</span>
                            </div>
                        </div>

                        @if($booking->catatan)
                            <div style="margin-top: 12px; padding: 10px 12px; background: #EEF2FF; border: 1px solid #C7D2FE; border-radius: 8px; font-size: 12px; color: #312E81; line-height: 1.4;">
                                <strong style="color: #4F46E5;">Catatan / Add-ons:</strong> {{ $booking->catatan }}
                            </div>
                        @endif
                    </div>

                    {{-- Merchant Details --}}
                    <div class="party-card">
                        <div class="party-title">
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            <span>PENYEDIA LAYANAN (MERCHANT)</span>
                        </div>
                        <p style="font-size: 16px; font-weight: 700; color: #0F172A; margin: 0 0 4px 0;">
                            {{ $booking->tenant->namabisnis }}
                        </p>
                        <div style="font-size: 13px; color: #475569; display: flex; flex-direction: column; gap: 3px;">
                            @if($booking->tenant->alamat)
                                <div class="flex items-start gap-2">
                                    <span class="text-slate-400 text-xs">Alamat:</span>
                                    <span class="text-slate-700 leading-snug">{{ $booking->tenant->alamat }}</span>
                                </div>
                            @endif
                            @if($booking->tenant->nomorhp)
                                <div class="flex items-center gap-2">
                                    <span class="text-slate-400 text-xs">Kontak:</span>
                                    <span class="font-medium text-slate-800">{{ $booking->tenant->nomorhp }}</span>
                                </div>
                            @endif
                            @if($booking->tenant->user?->email)
                                <div class="flex items-center gap-2">
                                    <span class="text-slate-400 text-xs">Email:</span>
                                    <span class="text-slate-700">{{ $booking->tenant->user->email }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- ── RESERVATION ITEM TABLE ── --}}
                <div class="table-container">
                    <table class="invoice-table">
                        <thead>
                            <tr>
                                <th style="width: 44%;">Layanan & Rincian</th>
                                <th style="width: 26%;">Jadwal Sesi</th>
                                <th style="width: 12%; text-align: center;">Durasi</th>
                                <th style="width: 8%; text-align: center;">Qty</th>
                                <th style="width: 10%; text-align: right;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <p style="font-weight: 700; color: #0F172A; margin: 0; font-size: 14px;">
                                        {{ $booking->layanan->namalayanan ?? 'Layanan Booking' }}
                                    </p>
                                    @if($booking->layanan?->deskripsi)
                                        <p style="font-size: 12px; color: #64748B; margin: 3px 0 0 0; line-height: 1.4;">
                                            {{ \Illuminate\Support\Str::limit($booking->layanan->deskripsi, 120) }}
                                        </p>
                                    @endif
                                </td>
                                <td>
                                    <p style="font-weight: 600; color: #0F172A; margin: 0; font-size: 13px;">
                                        {{ $bookingDate->translatedFormat('d M Y') }}
                                    </p>
                                    <p style="font-size: 12px; color: #4F46E5; font-weight: 600; margin: 2px 0 0 0;">
                                        {{ $jamFormatted }} WIB
                                    </p>
                                    @if($booking->rescheduled_from_date)
                                        <span style="display: inline-block; font-size: 10px; font-weight: 700; color: #D97706; background: #FEF3C7; padding: 2px 6px; border-radius: 4px; margin-top: 4px;">
                                            Jadwal Reschedule
                                        </span>
                                    @endif
                                </td>
                                <td style="text-align: center; color: #475569; font-size: 13px;">
                                    {{ $booking->layanan->durasi ?? 60 }} {{ $booking->layanan->satuan_durasi ?? 'menit' }}
                                </td>
                                <td style="text-align: center; font-weight: 600; color: #0F172A;">
                                    1
                                </td>
                                <td style="text-align: right; font-weight: 800; color: #0F172A; font-size: 14px;">
                                    {{ $booking->priceLabel }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- ── TOTALS & SUMMARY ── --}}
                <div class="totals-section">
                    <div class="totals-box">
                        <div class="totals-row">
                            <span>Subtotal Layanan</span>
                            <span style="font-weight: 600; color: #0F172A;">{{ $booking->priceLabel }}</span>
                        </div>
                        <div class="totals-row">
                            <span>Pajak & Layanan</span>
                            <span style="font-weight: 600; color: #059669;">Termasuk (0%)</span>
                        </div>
                        <div class="totals-row">
                            <span>Biaya Platform</span>
                            <span style="font-weight: 600; color: #059669;">Gratis</span>
                        </div>
                        <div class="totals-row grand-total">
                            <span>TOTAL BAYAR</span>
                            <span class="amount font-mono">{{ $booking->priceLabel }}</span>
                        </div>
                    </div>
                </div>

                {{-- ── PAYMENT VERIFICATION DETAILS (IF PAYMENT RECORD EXISTS) ── --}}
                @if($booking->payment)
                <div class="payment-info-box">
                    <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #E2E8F0; padding-bottom: 10px;">
                        <div class="party-title" style="margin-bottom: 0;">
                            <svg class="w-3.5 h-3.5 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                            </svg>
                            <span style="color: #4F46E5;">RINCIAN TRANSAKSI SISTEM</span>
                        </div>
                        <span style="font-size: 11px; font-weight: 700; color: #059669; background: #ECFDF5; padding: 2px 8px; border-radius: 6px; border: 1px solid #A7F3D0;">
                            ✓ TERVERIFIKASI
                        </span>
                    </div>

                    <div class="payment-grid">
                        <div>
                            <p style="font-size: 11px; color: #64748B; margin: 0 0 3px 0; text-transform: uppercase;">Metode Pembayaran</p>
                            <p style="font-size: 13px; font-weight: 700; color: #0F172A; margin: 0; text-transform: uppercase;">
                                {{ str_replace('_', ' ', $booking->payment->metode ?? 'Midtrans') }}
                            </p>
                        </div>
                        <div>
                            <p style="font-size: 11px; color: #64748B; margin: 0 0 3px 0; text-transform: uppercase;">Order ID Midtrans</p>
                            <p class="font-mono" style="font-size: 12px; font-weight: 700; color: #0F172A; margin: 0; word-break: break-all;">
                                {{ $booking->payment->order_id ?? '-' }}
                            </p>
                        </div>
                        <div>
                            <p style="font-size: 11px; color: #64748B; margin: 0 0 3px 0; text-transform: uppercase;">Waktu Verifikasi</p>
                            <p style="font-size: 13px; font-weight: 600; color: #0F172A; margin: 0;">
                                {{ ($booking->payment->updated_at ?? $booking->created_at)->translatedFormat('d M Y, H:i') }} WIB
                            </p>
                        </div>
                        <div>
                            <p style="font-size: 11px; color: #64748B; margin: 0 0 3px 0; text-transform: uppercase;">Status Pembayaran</p>
                            <p style="font-size: 13px; font-weight: 800; color: #059669; margin: 0;">
                                {{ $booking->payment->status === 'sukses' ? 'LUNAS (SUKSES)' : strtoupper($booking->payment->status) }}
                            </p>
                        </div>
                    </div>
                </div>
                @endif

                {{-- ── FOOTER SEAL & VALIDATION ── --}}
                <div class="footer-seal">
                    <div style="flex: 1; min-width: 260px;">
                        <p style="font-size: 12px; color: #64748B; line-height: 1.6; margin: 0;">
                            Dokumen invoice ini diterbitkan secara resmi oleh sistem <strong>BookQu</strong> atas nama <strong>{{ $booking->tenant->namabisnis }}</strong>.<br/>
                            Kode booking <strong class="font-mono text-indigo-700 font-bold">{{ $booking->booking_code }}</strong> adalah bukti sah reservasi dan transaksi Anda. Tunjukkan dokumen ini saat kedatangan.
                        </p>
                        <p style="font-size: 11px; color: #94A3B8; margin-top: 6px;">
                            Bantuan atau pertanyaan? Hubungi kontak merchant di <strong>{{ $booking->tenant->nomorhp ?? '-' }}</strong>.
                        </p>
                    </div>

                    <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 8px;">
                        {{-- Barcode / Verification Badge --}}
                        <div style="background: #F1F5F9; border: 1px solid #CBD5E1; border-radius: 8px; padding: 6px 14px; text-align: center;">
                            <div class="font-mono" style="font-size: 13px; font-weight: 800; letter-spacing: 0.15em; color: #334155;">
                                * {{ $booking->booking_code }} *
                            </div>
                            <span style="font-size: 10px; color: #64748B; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">
                                BOOKQU VERIFIED SEAL
                            </span>
                        </div>
                        <span style="font-size: 11px; color: #94A3B8;">
                            Powered by <strong style="color: #4F46E5;">BookQu</strong>
                        </span>
                    </div>
                </div>

            </div>
        </div>
    </main>

    {{-- ── SCRIPT FOR DOWNLOADING INVOICE AS IMAGE ── --}}
    <script>
        async function saveInvoiceAsImage() {
            const btn = document.getElementById('btn-save-image');
            const originalContent = btn.innerHTML;
            btn.innerHTML = `
                <svg class="w-4 h-4 animate-spin text-slate-600 inline-block" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Menyiapkan...</span>
            `;
            btn.disabled = true;

            try {
                const invoiceEl = document.getElementById('invoice-card');
                const canvas = await html2canvas(invoiceEl, {
                    scale: 2,
                    useCORS: true,
                    backgroundColor: '#ffffff',
                    logging: false
                });

                const link = document.createElement('a');
                link.download = 'Invoice-{{ $booking->booking_code }}.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
            } catch (err) {
                console.error('Failed to export invoice image:', err);
                alert('Gagal mendownload gambar. Silakan gunakan tombol Cetak / Simpan PDF sebagai alternatif.');
            } finally {
                btn.innerHTML = originalContent;
                btn.disabled = false;
            }
        }
    </script>
</body>
</html>
