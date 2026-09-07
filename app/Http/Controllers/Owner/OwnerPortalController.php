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

        $view = in_array($request->query('view'), ['day', 'week', 'month']) ? $request->query('view') : 'week';
        $rawDate = $request->query('date');
        $currentDate = $rawDate ? \Carbon\Carbon::parse($rawDate) : \Carbon\Carbon::today();

        // Calculate navigation dates based on active viewMode
        $todayDate = \Carbon\Carbon::today()->toDateString();
        if ($view === 'day') {
            $prevDate = $currentDate->copy()->subDay()->toDateString();
            $nextDate = $currentDate->copy()->addDay()->toDateString();
            $dateLabel = $currentDate->translatedFormat('l, d F Y');
        } elseif ($view === 'month') {
            $prevDate = $currentDate->copy()->subMonth()->startOfMonth()->toDateString();
            $nextDate = $currentDate->copy()->addMonth()->startOfMonth()->toDateString();
            $dateLabel = $currentDate->translatedFormat('F Y');
        } else { // week
            $startOfWeek = $currentDate->copy()->startOfWeek();
            $endOfWeek = $currentDate->copy()->endOfWeek();
            $prevDate = $startOfWeek->copy()->subWeek()->toDateString();
            $nextDate = $startOfWeek->copy()->addWeek()->toDateString();
            $dateLabel = $startOfWeek->translatedFormat('d M') . ' - ' . $endOfWeek->translatedFormat('d M Y');
        }

        $selectedService = $request->query('service_id', 'all');
        $selectedStatus = $request->query('status', 'all');

        $services = \App\Models\Service::where('idtenant', $tenant->id)->orderBy('namalayanan')->get();

        // Date range covering month + edge weeks for smooth view transitions
        $rangeStart = $currentDate->copy()->startOfMonth()->startOfWeek();
        $rangeEnd = $currentDate->copy()->endOfMonth()->endOfWeek();

        // Blocked dates in range
        $blockedDates = \App\Models\OwnerBlockedDate::where('idtenant', $tenant->id)
            ->whereBetween('tanggal', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
            ->pluck('alasan', 'tanggal')
            ->toArray();

        // Schedules in range
        $schedulesQuery = \App\Models\Schedule::where('idtenant', $tenant->id)
            ->whereBetween('tanggal', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
            ->with(['layanan', 'bookings.payment']);

        if ($selectedService !== 'all' && is_numeric($selectedService)) {
            $schedulesQuery->where('idlayanan', (int) $selectedService);
        }

        if ($selectedStatus === 'available') {
            $schedulesQuery->where('status', 'tersedia')
                ->whereDoesntHave('bookings', fn($q) => $q->whereIn('status', ['pending', 'paid', 'completed']));
        } elseif ($selectedStatus === 'blocked') {
            $schedulesQuery->where('status', 'diblokir');
        } elseif ($selectedStatus !== 'all' && in_array($selectedStatus, ['paid', 'pending', 'completed', 'cancelled'])) {
            $schedulesQuery->whereHas('bookings', fn($q) => $q->where('status', $selectedStatus));
        }

        $schedules = $schedulesQuery->orderBy('tanggal')->orderBy('jam_mulai')->get();

        // Bookings in range
        $bookingsQuery = \App\Models\Booking::where('idtenant', $tenant->id)
            ->whereBetween('tanggalbooking', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
            ->with(['layanan', 'payment', 'schedule']);

        if ($selectedService !== 'all' && is_numeric($selectedService)) {
            $bookingsQuery->where('idlayanan', (int) $selectedService);
        }

        if ($selectedStatus === 'available' || $selectedStatus === 'blocked') {
            $bookingsQuery->whereRaw('1 = 0');
        } elseif ($selectedStatus !== 'all' && in_array($selectedStatus, ['paid', 'pending', 'completed', 'cancelled'])) {
            $bookingsQuery->where('status', $selectedStatus);
        }

        $bookings = $bookingsQuery->orderBy('tanggalbooking')->orderBy('jam')->get();

        // Week schedules specific for week view
        $weekStart = $currentDate->copy()->startOfWeek();
        $weekEnd = $currentDate->copy()->endOfWeek();
        $weekSchedules = $schedules->filter(function ($s) use ($weekStart, $weekEnd) {
            $t = \Carbon\Carbon::parse($s->tanggal);
            return $t->betweenIncluded($weekStart, $weekEnd);
        })->values();

        // Month bookings grouped by day (1..31) for month view
        $monthBookings = $bookings->filter(function ($b) use ($currentDate) {
            $t = \Carbon\Carbon::parse($b->tanggalbooking);
            return $t->month === $currentDate->month && $t->year === $currentDate->year;
        })->groupBy(fn($b) => \Carbon\Carbon::parse($b->tanggalbooking)->format('j'));

        // Month schedules grouped by day (1..31) for month view
        $monthSchedules = $schedules->filter(function ($s) use ($currentDate) {
            $t = \Carbon\Carbon::parse($s->tanggal);
            return $t->month === $currentDate->month && $t->year === $currentDate->year;
        })->groupBy(fn($s) => \Carbon\Carbon::parse($s->tanggal)->format('j'));

        return view('owner.owner-calendar', compact(
            'tenant',
            'services',
            'bookings',
            'schedules',
            'weekSchedules',
            'monthBookings',
            'monthSchedules',
            'blockedDates',
            'view',
            'currentDate',
            'prevDate',
            'nextDate',
            'todayDate',
            'dateLabel',
            'weekStart',
            'weekEnd',
            'selectedService',
            'selectedStatus'
        ));
    }

    public function scheduleReport(\Illuminate\Http\Request $request)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $period = $request->query('period', 'all');
        $now = \Carbon\Carbon::now();
        $startDate = null;
        $endDate = null;

        if ($period === 'today') {
            $startDate = $now->copy()->startOfDay()->toDateString();
            $endDate = $now->copy()->endOfDay()->toDateString();
        } elseif ($period === 'this_week') {
            $startDate = $now->copy()->startOfWeek()->toDateString();
            $endDate = $now->copy()->endOfWeek()->toDateString();
        } elseif ($period === 'this_month') {
            $startDate = $now->copy()->startOfMonth()->toDateString();
            $endDate = $now->copy()->endOfMonth()->toDateString();
        } elseif ($period === 'last_30_days') {
            $startDate = $now->copy()->subDays(30)->toDateString();
            $endDate = $now->copy()->toDateString();
        }

        $schedulesQuery = \App\Models\Schedule::where('idtenant', $tenant->id);
        if ($startDate && $endDate) {
            $schedulesQuery->whereBetween('tanggal', [$startDate, $endDate]);
        }

        $totalSlots = (clone $schedulesQuery)->count();
        $bookedSlots = (clone $schedulesQuery)
            ->whereHas('bookings', fn($q) => $q->whereIn('status', ['paid', 'completed']))
            ->count();
        $availableSlots = (clone $schedulesQuery)
            ->where('status', 'tersedia')
            ->whereDoesntHave('bookings', fn($q) => $q->whereIn('status', ['pending', 'paid', 'completed']))
            ->count();
        $utilizationRate = $totalSlots > 0 ? round(($bookedSlots / $totalSlots) * 100, 1) : 0;

        $bookingsQuery = \App\Models\Booking::where('idtenant', $tenant->id)
            ->whereIn('status', ['paid', 'completed'])
            ->with('layanan');

        if ($startDate && $endDate) {
            $bookingsQuery->whereBetween('tanggalbooking', [$startDate, $endDate]);
        }

        $bookings = $bookingsQuery->get();

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
            'resourceList',
            'period'
        ));
    }

    public function exportScheduleReport(\Illuminate\Http\Request $request)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $period = $request->query('period', 'all');
        $now = \Carbon\Carbon::now();
        $startDate = null;
        $endDate = null;

        if ($period === 'today') {
            $startDate = $now->copy()->startOfDay()->toDateString();
            $endDate = $now->copy()->endOfDay()->toDateString();
        } elseif ($period === 'this_week') {
            $startDate = $now->copy()->startOfWeek()->toDateString();
            $endDate = $now->copy()->endOfWeek()->toDateString();
        } elseif ($period === 'this_month') {
            $startDate = $now->copy()->startOfMonth()->toDateString();
            $endDate = $now->copy()->endOfMonth()->toDateString();
        } elseif ($period === 'last_30_days') {
            $startDate = $now->copy()->subDays(30)->toDateString();
            $endDate = $now->copy()->toDateString();
        }

        $schedulesQuery = \App\Models\Schedule::where('idtenant', $tenant->id)->with(['layanan', 'bookings']);
        if ($startDate && $endDate) {
            $schedulesQuery->whereBetween('tanggal', [$startDate, $endDate]);
        }
        $schedules = $schedulesQuery->orderBy('tanggal')->orderBy('jam_mulai')->get();

        $totalSlots = $schedules->count();
        $bookedSlots = $schedules->filter(fn($s) => $s->bookings->whereIn('status', ['paid', 'completed'])->isNotEmpty())->count();
        $availableSlots = $schedules->filter(fn($s) => $s->status === 'tersedia' && $s->bookings->whereIn('status', ['pending', 'paid', 'completed'])->isEmpty())->count();
        $utilizationRate = $totalSlots > 0 ? round(($bookedSlots / $totalSlots) * 100, 1) : 0;

        $filename = 'schedule-report-' . ($tenant->slug ?? 'tenant') . '-' . $period . '-' . date('Ymd') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($tenant, $schedules, $totalSlots, $bookedSlots, $availableSlots, $utilizationRate, $period) {
            $file = fopen('php://output', 'w');
            // BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, ['BOOKQU - SCHEDULE & UTILIZATION REPORT']);
            fputcsv($file, ['Bisnis', $tenant->namabisnis ?? '-']);
            fputcsv($file, ['Periode', ucfirst($period)]);
            fputcsv($file, ['Tanggal Ekspor', date('Y-m-d H:i:s')]);
            fputcsv($file, []);
            fputcsv($file, ['METRIK KPI', 'NILAI']);
            fputcsv($file, ['Total Slots', $totalSlots]);
            fputcsv($file, ['Booked Slots', $bookedSlots]);
            fputcsv($file, ['Available Slots', $availableSlots]);
            fputcsv($file, ['Utilization Rate (%)', $utilizationRate . '%']);
            fputcsv($file, []);
            fputcsv($file, ['RINCIAN SLOT OPERASIONAL']);
            fputcsv($file, ['Tanggal', 'Jam Mulai', 'Jam Selesai', 'Layanan', 'Tarif (Rp)', 'Status Availability', 'Nama Tamu / Pemesan']);

            foreach ($schedules as $s) {
                $b = $s->bookings->whereIn('status', ['paid', 'completed'])->first();
                fputcsv($file, [
                    $s->tanggal,
                    substr($s->jam_mulai, 0, 5),
                    substr($s->jam_selesai, 0, 5),
                    $s->layanan->namalayanan ?? '-',
                    $s->harga_override ?? $s->layanan->harga ?? 0,
                    $s->getAvailabilityStatus(),
                    $b ? $b->namapelanggan : '-',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
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
