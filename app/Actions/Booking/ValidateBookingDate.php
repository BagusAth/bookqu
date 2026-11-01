<?php

namespace App\Actions\Booking;

use App\Actions\Schedule\GetAvailableSchedules;
use App\Domain\Subscription\EntitlementRules;
use App\Domain\Subscription\SubscriptionUsage;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class ValidateBookingDate
{
    public function __construct(private GetAvailableSchedules $availability) {}

    public function execute(Tenant $tenant, Service $service, mixed $date, bool $simulate = false): string
    {
        Validator::make(['tanggal' => $date], ['tanggal' => ['required', 'date_format:Y-m-d']])->validate();
        $selected = Carbon::parse($date, 'Asia/Jakarta');
        $min = Carbon::today('Asia/Jakarta');
        if ($selected->lt($min) || $selected->gt($min->copy()->addDays(30))) {
            throw ValidationException::withMessages(['tanggal' => 'Tanggal yang dipilih di luar rentang pemesanan (hari ini sampai 30 hari ke depan).']);
        }
        if ($simulate) {
            return $selected->toDateString();
        }
        $subscription = Subscription::with('plan')->where('idtenant', $tenant->id)->latest()->first();
        if (! EntitlementRules::isUnlimitedBooking($subscription)) {
            $count = SubscriptionUsage::countMonthlyBookings((int) $tenant->id, $selected);
            $quota = EntitlementRules::canCreateBooking($subscription, $count, 1);
            if (! $quota['allowed']) {
                throw ValidationException::withMessages(['tanggal' => 'Kapasitas kuota booking bulanan bisnis ini telah penuh (maksimal '.$quota['maxBooking'].' booking/bulan). Silakan hubungi pemilik bisnis.']);
            }
        }
        $iso = $selected->toDateString();
        if (DB::table('owner_blocked_dates')->where('idtenant', $tenant->id)->whereDate('tanggal', $iso)->exists()) {
            throw ValidationException::withMessages(['tanggal' => 'Tanggal ini sedang ditutup oleh pemilik bisnis.']);
        }
        if ($this->availability->countAvailableSlotsOnDate($tenant, $service, $iso) < 1) {
            throw ValidationException::withMessages(['tanggal' => 'Tanggal ini sudah penuh. Silakan pilih tanggal lain.']);
        }

        return $iso;
    }
}
