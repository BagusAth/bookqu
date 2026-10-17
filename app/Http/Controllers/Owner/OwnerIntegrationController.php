<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Traits\ResolvesOwnerTenant;
use Illuminate\View\View;

class OwnerIntegrationController extends Controller
{
    use ResolvesOwnerTenant;

    public function index(): View
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $midtransConfigured = !empty($tenant->midtrans_server_key) || !empty(config('midtrans.server_key'));
        $mailConfigured = !empty(config('mail.default'));

        $integrations = [
            [
                'id'           => 'midtrans',
                'name'         => 'Midtrans Payment Gateway',
                'category'     => 'Payments',
                'description'  => 'Memproses pembayaran otomatis menggunakan QRIS, Virtual Account Bank (BCA, Mandiri, BNI, BRI), dan E-Wallet.',
                'status'       => $midtransConfigured ? 'connected' : 'disconnected',
                'is_active'    => true,
                'action_url'   => route('owner.settings.payment-setting'),
                'action_label' => 'Konfigurasi Midtrans',
            ],
            [
                'id'           => 'email',
                'name'         => 'Email SMTP & Notifications',
                'category'     => 'Communication',
                'description'  => 'Kirim email invoice, tiket reservasi otomatis, dan konfirmasi reschedule kepada customer.',
                'status'       => $mailConfigured ? 'connected' : 'disconnected',
                'is_active'    => true,
                'action_url'   => null,
                'action_label' => 'Aktif dari Sistem',
            ],
            [
                'id'           => 'whatsapp',
                'name'         => 'WhatsApp Automated Reminders',
                'category'     => 'Messaging',
                'description'  => 'Kirim notifikasi pengingat H-1 atau H-2 jam sebelum jadwal sesi reservasi customer via WhatsApp Gateway.',
                'status'       => 'coming_soon',
                'is_active'    => false,
                'action_url'   => null,
                'action_label' => 'Segera Hadir',
            ],
            [
                'id'           => 'gcal',
                'name'         => 'Google Calendar Sync',
                'category'     => 'Calendar',
                'description'  => 'Sinkronisasi dua arah setiap booking yang masuk langsung ke kalender Google pribadi atau kalender tim.',
                'status'       => 'coming_soon',
                'is_active'    => false,
                'action_url'   => null,
                'action_label' => 'Segera Hadir',
            ],
            [
                'id'           => 'analytics',
                'name'         => 'Google Analytics & Meta Pixel',
                'category'     => 'Tracking',
                'description'  => 'Lacak kunjungan halaman booking, konversi reservasi, dan optimalkan kampanye iklan digital Anda.',
                'status'       => 'coming_soon',
                'is_active'    => false,
                'action_url'   => null,
                'action_label' => 'Segera Hadir',
            ],
        ];

        return view('owner.integrations', compact('tenant', 'integrations'));
    }
}
