<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ResolvesOwnerTenant;

class OwnerPortalController extends Controller
{
    use ResolvesOwnerTenant;

    public function calendar(Request $request)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $services = \App\Models\Service::where('idtenant', $tenant->id)->get();
        
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();

        $weekSchedules = \App\Models\Schedule::where('idtenant', $tenant->id)
            ->whereBetween('tanggal', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
            ->with(['layanan', 'bookings'])
            ->get();

        $bookings = \App\Models\Booking::where('idtenant', $tenant->id)
            ->with('layanan')
            ->orderBy('tanggalbooking')
            ->orderBy('jam')
            ->get();

        $monthBookings = \App\Models\Booking::where('idtenant', $tenant->id)
            ->whereMonth('tanggalbooking', now()->month)
            ->whereYear('tanggalbooking', now()->year)
            ->get()
            ->groupBy(fn($b) => \Carbon\Carbon::parse($b->tanggalbooking)->format('j'));

        return view('owner.owner-calendar', compact('tenant', 'services', 'bookings', 'weekSchedules', 'monthBookings'));
    }

    public function scheduleReport()
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $totalSlots = \App\Models\Schedule::where('idtenant', $tenant->id)->count();
        $bookedSlots = \App\Models\Schedule::where('idtenant', $tenant->id)
            ->whereHas('bookings', fn($q) => $q->whereIn('status', ['paid', 'completed']))
            ->count();
        $availableSlots = max(0, $totalSlots - $bookedSlots);
        $utilizationRate = $totalSlots > 0 ? round(($bookedSlots / $totalSlots) * 100, 1) : 0;

        $bookings = \App\Models\Booking::where('idtenant', $tenant->id)
            ->whereIn('status', ['paid', 'completed'])
            ->with('layanan')
            ->get();

        // Hourly distribution
        $hourlyCounts = [];
        for ($h = 8; $h <= 21; $h++) {
            $formattedH = sprintf('%02d:00', $h);
            $hourlyCounts[$formattedH] = 0;
        }

        foreach ($bookings as $b) {
            if ($b->jam) {
                $h = substr($b->jam, 0, 2) . ':00';
                if (isset($hourlyCounts[$h])) {
                    $hourlyCounts[$h]++;
                }
            }
        }

        // Peak and low demand hours
        $sortedHours = $hourlyCounts;
        arsort($sortedHours);
        $hasHourData = !empty($sortedHours) && reset($sortedHours) > 0;
        $peakHour = $hasHourData ? array_key_first($sortedHours) : 'Tidak ada data';
        $lowHour = $hasHourData ? array_key_last($sortedHours) : 'Tidak ada data';

        // Daily distribution (1=Monday .. 7=Sunday)
        $dailyCounts = [
            'Senin'  => 0,
            'Selasa' => 0,
            'Rabu'   => 0,
            'Kamis'  => 0,
            'Jumat'  => 0,
            'Sabtu'  => 0,
            'Minggu' => 0,
        ];

        $dayMap = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];

        foreach ($bookings as $b) {
            if ($b->tanggalbooking) {
                $dayOfWeek = \Carbon\Carbon::parse($b->tanggalbooking)->dayOfWeekIso;
                if (isset($dayMap[$dayOfWeek])) {
                    $dailyCounts[$dayMap[$dayOfWeek]]++;
                }
            }
        }

        $sortedDays = $dailyCounts;
        arsort($sortedDays);
        $hasDayData = !empty($sortedDays) && reset($sortedDays) > 0;
        $peakDay = $hasDayData ? array_key_first($sortedDays) : 'Tidak ada data';
        $lowDay = $hasDayData ? array_key_last($sortedDays) : 'Tidak ada data';

        // Staff workload based on actual assigned service bookings
        $staffMembers = \App\Models\Staff::where('idtenant', $tenant->id)->with('services')->get()->map(function ($s) use ($bookings) {
            $serviceIds = $s->services->pluck('id')->toArray();
            $handledBookingsCount = !empty($serviceIds)
                ? $bookings->whereIn('idlayanan', $serviceIds)->count()
                : 0;
            return [
                'name'           => $s->name,
                'role'           => $s->role,
                'services_count' => count($serviceIds),
                'services_names' => $s->services->pluck('namalayanan')->implode(', '),
                'count'          => $handledBookingsCount,
            ];
        });

        // Resource workload based on actual assigned service bookings
        $resourceList = \App\Models\Resource::where('idtenant', $tenant->id)->with('services')->get()->map(function ($r) use ($bookings) {
            $serviceIds = $r->services->pluck('id')->toArray();
            $handledBookingsCount = !empty($serviceIds)
                ? $bookings->whereIn('idlayanan', $serviceIds)->count()
                : 0;
            return [
                'name'           => $r->name,
                'type'           => $r->type,
                'capacity'       => $r->capacity,
                'services_count' => count($serviceIds),
                'services_names' => $r->services->pluck('namalayanan')->implode(', '),
                'count'          => $handledBookingsCount,
            ];
        });

        return view('owner.owner-schedule-report', compact(
            'tenant',
            'totalSlots',
            'bookedSlots',
            'availableSlots',
            'utilizationRate',
            'hourlyCounts',
            'peakHour',
            'lowHour',
            'dailyCounts',
            'peakDay',
            'lowDay',
            'staffMembers',
            'resourceList'
        ));
    }

    public function categories()
    {
        $tenant = $this->resolveTenant();
        return view('owner.owner-categories', compact('tenant'));
    }

    public function staffResources()
    {
        $tenant = $this->resolveTenant();
        return view('owner.owner-staff-resources', compact('tenant'));
    }

    public function additionalItems()
    {
        $tenant = $this->resolveTenant();
        return view('owner.owner-additional-items', compact('tenant'));
    }

    public function vouchers()
    {
        $tenant = $this->resolveTenant();
        return view('owner.owner-vouchers', compact('tenant'));
    }

    public function reviews()
    {
        $tenant = $this->resolveTenant();
        return view('owner.owner-reviews', compact('tenant'));
    }

    public function customers(Request $request)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            return view('owner.owner-customers', [
                'tenant' => null,
                'customers' => collect(),
                'totalCustomers' => 0,
                'totalSpentAll' => 0,
                'totalBookingsAll' => 0,
            ]);
        }

        $bookings = \App\Models\Booking::where('idtenant', $tenant->id)
            ->with(['layanan', 'payment'])
            ->orderBy('created_at', 'desc')
            ->get();

        $notesMap = \App\Models\CustomerNote::where('idtenant', $tenant->id)
            ->pluck('notes', 'customer_identifier');

        $grouped = $bookings->groupBy(function ($b) {
            return strtolower(trim($b->email ?: ($b->nomorhp ?: ('guest-' . $b->id))));
        });

        $customers = $grouped->map(function ($userBookings, $identifier) use ($notesMap) {
            $first = $userBookings->first();
            $totalBookings = $userBookings->count();
            $paidBookings = $userBookings->filter(fn($b) => in_array($b->status, ['paid', 'completed']));
            $totalSpent = $paidBookings->sum(fn($b) => $b->layanan?->harga ?? 0);
            $lastBooking = $userBookings->sortByDesc('tanggalbooking')->first();
            $today = \Carbon\Carbon::today()->toDateString();
            $upcomingBooking = $userBookings->filter(fn($b) => $b->tanggalbooking && $b->tanggalbooking->toDateString() >= $today && in_array($b->status, ['paid', 'pending']))->sortBy('tanggalbooking')->first();

            return [
                'identifier' => (string) $identifier,
                'name' => $first->namapelanggan ?: 'Customer',
                'email' => $first->email ?: '-',
                'phone' => $first->nomorhp ?: '-',
                'notes' => $notesMap[$identifier] ?? '',
                'total_bookings' => $totalBookings,
                'total_spent' => $totalSpent,
                'formatted_spent' => 'Rp ' . number_format($totalSpent, 0, ',', '.'),
                'last_booking' => $lastBooking?->tanggalbooking ? $lastBooking->tanggalbooking->format('d M Y') : '-',
                'upcoming_booking' => $upcomingBooking ? ($upcomingBooking->tanggalbooking->format('d M Y') . ' ' . $upcomingBooking->jam) : '-',
                'services_used' => $userBookings->map(fn($b) => $b->layanan?->namalayanan)->filter()->unique()->values()->toArray(),
                'bookings' => $userBookings->map(fn($b) => [
                    'id' => $b->id,
                    'code' => $b->booking_code ?? ('BKQ-' . $b->id),
                    'service' => $b->layanan?->namalayanan ?? 'Service',
                    'price' => 'Rp ' . number_format($b->layanan?->harga ?? 0, 0, ',', '.'),
                    'date' => $b->tanggalbooking ? $b->tanggalbooking->format('d M Y') : '-',
                    'time' => $b->jam,
                    'status' => $b->status,
                    'notes' => $b->catatan ?: '-',
                ])->values(),
            ];
        })->values();

        $totalCustomers = $customers->count();
        $totalSpentAll = $customers->sum('total_spent');
        $totalBookingsAll = $bookings->count();

        return view('owner.owner-customers', compact('tenant', 'customers', 'totalCustomers', 'totalSpentAll', 'totalBookingsAll'));
    }

    public function saveCustomerNote(Request $request)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $validated = $request->validate([
            'customer_identifier' => 'required|string|max:190',
            'notes'               => 'nullable|string|max:2000',
        ]);

        \App\Models\CustomerNote::updateOrCreate(
            [
                'idtenant'            => $tenant->id,
                'customer_identifier' => $validated['customer_identifier'],
            ],
            [
                'notes' => $validated['notes'] ?? '',
            ]
        );

        return redirect()->route('owner.customers')->with('sukses', 'Catatan customer berhasil disimpan.');
    }

    public function appearance()
    {
        $tenant = $this->resolveTenant();
        return view('owner.owner-appearance', compact('tenant'));
    }

    public function updateAppearance(Request $request)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $validated = $request->validate([
            'theme_color'  => 'nullable|string|max:20',
            'button_style' => 'nullable|string|max:50',
            'font_family'  => 'nullable|string|max:100',
            'card_style'   => 'nullable|string|max:50',
            'deskripsi'    => 'nullable|string|max:1000',
        ]);

        $tenant->update([
            'theme_color'  => $validated['theme_color'] ?? $tenant->theme_color,
            'button_style' => $validated['button_style'] ?? $tenant->button_style,
            'font_family'  => $validated['font_family'] ?? $tenant->font_family,
            'card_style'   => $validated['card_style'] ?? $tenant->card_style,
            'deskripsi'    => $validated['deskripsi'] ?? $tenant->deskripsi,
        ]);

        return redirect()->route('owner.settings.appearance')
            ->with('sukses', 'Pengaturan tema & tampilan publik berhasil disimpan!');
    }

    public function paymentSettings()
    {
        $tenant = $this->resolveTenant();
        $payouts = \App\Models\OwnerPayout::where('idtenant', $tenant?->id)->orderByDesc('created_at')->limit(10)->get();
        $transactions = \App\Models\Payment::where('idtenant', $tenant?->id)->with('booking.layanan')->orderByDesc('created_at')->limit(15)->get();
        return view('owner.owner-payment-settings', compact('tenant', 'payouts', 'transactions'));
    }

    public function assets()
    {
        $tenant = $this->resolveTenant();
        return view('owner.owner-assets', compact('tenant'));
    }

    public function balance()
    {
        $tenant = $this->resolveTenant();
        $availableBalance = $tenant?->saldo_platform ?? 0;

        $pendingSettlement = \App\Models\Payment::where('idtenant', $tenant?->id)
            ->where('status', 'pending')
            ->where('tipe', 'booking')
            ->sum('jumlah');

        $totalEarnings = \App\Models\Payment::where('idtenant', $tenant?->id)
            ->where('status', 'sukses')
            ->where('tipe', 'booking')
            ->sum('jumlah');

        $payouts = \App\Models\OwnerPayout::where('idtenant', $tenant?->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('owner.owner-balance', compact('tenant', 'availableBalance', 'pendingSettlement', 'totalEarnings', 'payouts'));
    }

    public function integrations()
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $midtransConfigured = !empty($tenant->midtrans_server_key) || !empty(config('midtrans.server_key'));
        $mailConfigured = !empty(config('mail.default'));

        $integrations = [
            [
                'id' => 'midtrans',
                'name' => 'Midtrans Payment Gateway',
                'category' => 'Payments',
                'description' => 'Memproses pembayaran otomatis menggunakan QRIS, Virtual Account Bank (BCA, Mandiri, BNI, BRI), dan E-Wallet.',
                'status' => $midtransConfigured ? 'connected' : 'disconnected',
                'is_active' => true,
                'action_url' => route('owner.settings.payment-setting'),
                'action_label' => 'Konfigurasi Midtrans',
            ],
            [
                'id' => 'email',
                'name' => 'Email SMTP & Notifications',
                'category' => 'Communication',
                'description' => 'Kirim email invoice, tiket reservasi otomatis, dan konfirmasi reschedule kepada customer.',
                'status' => $mailConfigured ? 'connected' : 'disconnected',
                'is_active' => true,
                'action_url' => null,
                'action_label' => 'Aktif dari Sistem',
            ],
            [
                'id' => 'whatsapp',
                'name' => 'WhatsApp Automated Reminders',
                'category' => 'Messaging',
                'description' => 'Kirim notifikasi pengingat H-1 atau H-2 jam sebelum jadwal sesi reservasi customer via WhatsApp Gateway.',
                'status' => 'coming_soon',
                'is_active' => false,
                'action_url' => null,
                'action_label' => 'Segera Hadir',
            ],
            [
                'id' => 'gcal',
                'name' => 'Google Calendar Sync',
                'category' => 'Calendar',
                'description' => 'Sinkronisasi dua arah setiap booking yang masuk langsung ke kalender Google pribadi atau kalender tim.',
                'status' => 'coming_soon',
                'is_active' => false,
                'action_url' => null,
                'action_label' => 'Segera Hadir',
            ],
            [
                'id' => 'analytics',
                'name' => 'Google Analytics & Meta Pixel',
                'category' => 'Tracking',
                'description' => 'Lacak kunjungan halaman booking, konversi reservasi, dan optimalkan kampanye iklan digital Anda.',
                'status' => 'coming_soon',
                'is_active' => false,
                'action_url' => null,
                'action_label' => 'Segera Hadir',
            ],
        ];

        return view('owner.owner-integrations', compact('tenant', 'integrations'));
    }
}
