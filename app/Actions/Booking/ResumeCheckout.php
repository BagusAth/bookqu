<?php

namespace App\Actions\Booking;

use App\Actions\CustomerAccess\CustomerAccessLinks;
use App\Actions\CustomerAccess\IssueCustomerAccess;
use App\Domain\CustomerAccess\AccessProfile;
use App\Models\Tenant;

final class ResumeCheckout
{
    public function execute(Tenant $tenant, mixed $fingerprint): ?string
    {
        $stored = session('booking_checkout.fingerprint');
        if (! is_string($fingerprint) || $fingerprint === '' || ! is_string($stored) || ! hash_equals($stored, $fingerprint)) {
            return null;
        }
        // A new tab's selection must not be replaced by an older checkout.
        $selection = session('booking', []);
        if ($selection !== []) {
            $paymentId = session('booking_checkout.payment_id');
            $pending = app(StorefrontCheckoutState::class)->pending($tenant);
            if ($pending === null || $pending['payment']->id !== $paymentId) {
                return null;
            }
            $ids = $pending['payment']->bookings->pluck('idschedule')->map(fn ($id) => (int) $id)->sort()->values()->all();
            $selectedIds = collect($selection['schedule_ids'] ?? [])->map(fn ($id) => (int) $id)->sort()->values()->all();
            if ($ids !== $selectedIds) {
                return null;
            }
        }
        $pending = app(StorefrontCheckoutState::class)->pending($tenant);
        if ($pending === null) {
            return null;
        }
        $issued = app(IssueCustomerAccess::class)->execute($pending['payment'], AccessProfile::Checkout);

        return app(CustomerAccessLinks::class)->entry($issued);
    }
}
