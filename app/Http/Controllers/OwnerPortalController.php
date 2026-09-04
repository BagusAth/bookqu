<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ResolvesOwnerTenant;

class OwnerPortalController extends Controller
{
    use ResolvesOwnerTenant;

    public function calendar()
    {
        $tenant = $this->resolveTenant();
        $services = \App\Models\Service::where('idtenant', $tenant?->id)->get();
        $bookings = \App\Models\Booking::where('idtenant', $tenant?->id)
            ->with('layanan')
            ->orderBy('tanggalbooking')
            ->orderBy('jam')
            ->limit(100)
            ->get();

        return view('owner.owner-calendar', compact('tenant', 'services', 'bookings'));
    }

    public function scheduleReport()
    {
        $tenant = $this->resolveTenant();
        return view('owner.owner-schedule-report', compact('tenant'));
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

        $grouped = $bookings->groupBy(function ($b) {
            return strtolower($b->email ?: ($b->nomorhp ?: ('guest-' . $b->id)));
        });

        $customers = $grouped->map(function ($userBookings) {
            $first = $userBookings->first();
            $totalBookings = $userBookings->count();
            $paidBookings = $userBookings->filter(fn($b) => in_array($b->status, ['paid', 'completed']));
            $totalSpent = $paidBookings->sum(fn($b) => $b->layanan?->harga ?? 0);
            $lastBooking = $userBookings->sortByDesc('tanggalbooking')->first();
            $today = \Carbon\Carbon::today()->toDateString();
            $upcomingBooking = $userBookings->filter(fn($b) => $b->tanggalbooking && $b->tanggalbooking->toDateString() >= $today && in_array($b->status, ['paid', 'pending']))->sortBy('tanggalbooking')->first();

            return [
                'name' => $first->namapelanggan ?: 'Customer',
                'email' => $first->email ?: '-',
                'phone' => $first->nomorhp ?: '-',
                'total_bookings' => $totalBookings,
                'total_spent' => $totalSpent,
                'formatted_spent' => 'Rp ' . number_format($totalSpent, 0, ',', '.'),
                'last_booking' => $lastBooking?->tanggalbooking ? $lastBooking->tanggalbooking->format('d M Y') : '-',
                'upcoming_booking' => $upcomingBooking ? ($upcomingBooking->tanggalbooking->format('d M Y') . ' ' . $upcomingBooking->jam) : '-',
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

    public function appearance()
    {
        $tenant = $this->resolveTenant();
        return view('owner.owner-appearance', compact('tenant'));
    }

    public function paymentSettings()
    {
        $tenant = $this->resolveTenant();
        $payouts = \App\Models\OwnerPayout::where('idtenant', $tenant?->id)->orderByDesc('created_at')->limit(10)->get();
        $transactions = \App\Models\Payment::where('idtenant', $tenant?->id)->with('booking')->orderByDesc('created_at')->limit(10)->get();
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
        return view('owner.owner-balance', compact('tenant'));
    }

    public function integrations()
    {
        $tenant = $this->resolveTenant();
        return view('owner.owner-integrations', compact('tenant'));
    }
}
