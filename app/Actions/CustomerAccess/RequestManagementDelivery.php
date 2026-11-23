<?php

namespace App\Actions\CustomerAccess;

use App\Actions\Schedule\ClaimSlots;
use App\Domain\CustomerAccess\AccessContext;
use App\Jobs\SendManagementAccess;
use App\Models\Booking;
use App\Models\CustomerAccessDelivery;
use App\Models\Payment;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class RequestManagementDelivery
{
    public function execute(Payment|Booking $subject, string $reason, ?AccessContext $source = null): bool
    {
        return app(TenantContext::class)->run((int) $subject->idtenant, fn (): bool => DB::transaction(function () use ($subject, $reason, $source): bool {
            $tenantId = (int) $subject->idtenant;
            ClaimSlots::lockTenant($tenantId);
            $subject->refresh();
            abort_unless((int) $subject->idtenant === $tenantId, 404);
            if ($subject instanceof Booking && $subject->idpayment !== null) {
                $subject = Payment::findOrFail($subject->idpayment);
            }
            if ($subject instanceof Payment) {
                $bookings = app(LoadAccessSubject::class)->paymentBookings($subject);
            } else {
                $bookings = collect([$subject]);
            }
            abort_if($bookings->isEmpty() || $bookings->contains(fn (Booking $booking) => (int) $booking->idtenant !== (int) $subject->idtenant), 404);
            $recipient = $source?->grant->recipient ?? ($subject instanceof Payment ? $subject->email_pembayar : $subject->email);
            if (! $recipient) {
                return false;
            }
            $delivery = CustomerAccessDelivery::create([
                'idtenant' => $subject->idtenant,
                'payment_id' => $subject instanceof Payment ? $subject->id : null,
                'booking_id' => $subject instanceof Booking ? $subject->id : null,
                'source_grant_id' => $source?->grant->id,
                'family_version' => (int) $subject->customer_access_version,
                'booking_ids' => $bookings->pluck('id')->map(fn ($id) => (int) $id)->all(),
                'recipient' => $recipient,
                'reason' => $reason,
            ]);
            DB::afterCommit(function () use ($delivery): void {
                try {
                    SendManagementAccess::dispatch((int) $delivery->id);
                } catch (\Throwable) {
                    Log::warning('Customer access delivery failed after commit', ['delivery_id' => $delivery->id]);
                }
            });

            return true;
        }));
    }
}
