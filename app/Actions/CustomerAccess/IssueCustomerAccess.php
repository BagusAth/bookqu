<?php

namespace App\Actions\CustomerAccess;

use App\Actions\Schedule\ClaimSlots;
use App\Domain\CustomerAccess\AccessProfile;
use App\Domain\CustomerAccess\AccessRules;
use App\Domain\CustomerAccess\IssuedGrant;
use App\Models\Booking;
use App\Models\CustomerAccessGrant;
use App\Models\Payment;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class IssueCustomerAccess
{
    public function execute(Payment|Booking $subject, AccessProfile $profile, ?string $recipient = null): IssuedGrant
    {
        return DB::transaction(function () use ($subject, $profile, $recipient): IssuedGrant {
            $tenantId = (int) $subject->idtenant;
            ClaimSlots::lockTenant($tenantId);
            $subject->refresh();
            abort_unless((int) $subject->idtenant === $tenantId, 404);
            if ($subject instanceof Booking && $subject->idpayment !== null) {
                // scope-bypass: trusted booking subject; payment must belong to that same tenant.
                $subject = Payment::withoutGlobalScopes()->where('idtenant', $tenantId)->findOrFail($subject->idpayment);
            }
            abort_if($subject instanceof Payment && $subject->tipe !== 'booking', 404);
            if ($subject instanceof Payment) {
                // scope-bypass: validate the full group, including any foreign-tenant membership, before issuance.
                $bookings = Booking::withoutGlobalScopes()->with('layanan')->where('idpayment', $subject->id)->orderBy('id')->get();
            } else {
                $bookings = collect([$subject]);
            }
            abort_if($bookings->isEmpty() || $bookings->contains(fn (Booking $booking) => (int) $booking->idtenant !== $tenantId), 404);
            abort_if($profile === AccessProfile::Checkout && ! $subject instanceof Payment, 404);
            $issued = CarbonImmutable::now();
            $latestEnd = $bookings->map(fn (Booking $booking) => CarbonImmutable::parse(
                CarbonImmutable::parse($booking->getRawOriginal('tanggalbooking'))->toDateString().' '.$booking->jam, 'Asia/Jakarta'
            )->addMinutes((int) ($booking->layanan instanceof Service ? $booking->layanan->durasi : 60)))->max();
            $deadline = $subject instanceof Payment && $subject->expired_at !== null ? CarbonImmutable::parse($subject->getRawOriginal('expired_at')) : null;
            // Settled free payments have no pending deadline; capture issuance for their checkout invoice.
            if ($subject instanceof Payment && $subject->status !== 'pending' && $deadline === null) {
                $deadline = $issued;
            }
            $secret = bin2hex(random_bytes(32));
            $grant = CustomerAccessGrant::create([
                'selector' => bin2hex(random_bytes(16)),
                'secret_hash' => hash('sha256', $secret),
                'idtenant' => $tenantId,
                'profile' => $profile,
                'payment_id' => $subject instanceof Payment ? $subject->id : null,
                'booking_id' => $subject instanceof Booking ? $subject->id : null,
                'booking_ids' => $bookings->pluck('id')->map(fn ($id) => (int) $id)->all(),
                'family_version' => (int) $subject->customer_access_version,
                'recipient' => $recipient ?? ($subject instanceof Payment ? $subject->email_pembayar : $subject->email),
                'expires_at' => AccessRules::expiry($profile, $issued, $deadline, $latestEnd),
            ]);

            return new IssuedGrant($grant, $grant->selector.'.'.$secret);
        });
    }
}
