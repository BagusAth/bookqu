<?php

namespace App\Actions\CustomerAccess;

use App\Actions\Schedule\ClaimSlots;
use App\Domain\CustomerAccess\AccessContext;
use App\Jobs\SendManagementAccess;
use App\Models\Booking;
use App\Models\CustomerAccessDelivery;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class RequestManagementDelivery
{
    public function execute(Payment|Booking $subject, string $reason, ?AccessContext $source = null): void
    {
        DB::transaction(function () use ($subject, $reason, $source): void {
            ClaimSlots::lockTenant((int) $subject->idtenant);
            $subject->refresh();
            if ($subject instanceof Booking && $subject->idpayment !== null) {
                // scope-bypass: trusted business booking; require payment ownership before creating delivery intent.
                $subject = Payment::withoutGlobalScopes()->where('idtenant', $subject->idtenant)->findOrFail($subject->idpayment);
            }
            if ($subject instanceof Payment) {
                // scope-bypass: complete group snapshot is checked again by the delivery handler before minting.
                $bookings = Booking::withoutGlobalScopes()->where('idpayment', $subject->id)->orderBy('id')->get();
            } else {
                $bookings = collect([$subject]);
            }
            abort_if($bookings->isEmpty() || $bookings->contains(fn (Booking $booking) => (int) $booking->idtenant !== (int) $subject->idtenant), 404);
            $recipient = $source?->grant->recipient ?? ($subject instanceof Payment ? $subject->email_pembayar : $subject->email);
            if (! $recipient) {
                return;
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
                    // Keep current synchronous delivery until BQ-013/BQ-020 provide a supervised worker.
                    SendManagementAccess::dispatchSync((int) $delivery->id);
                } catch (\Throwable) {
                    Log::warning('Customer access delivery failed after commit', ['delivery_id' => $delivery->id]);
                }
            });
        });
    }
}
