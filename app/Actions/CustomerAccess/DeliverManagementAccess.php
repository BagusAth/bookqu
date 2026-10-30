<?php

namespace App\Actions\CustomerAccess;

use App\Actions\Schedule\ClaimSlots;
use App\Domain\CustomerAccess\AccessOperation;
use App\Domain\CustomerAccess\AccessProfile;
use App\Mail\BookingConfirmedMail;
use App\Mail\BookingGroupInvoiceMail;
use App\Mail\BookingRescheduledMail;
use App\Models\CustomerAccessDelivery;
use App\Models\CustomerAccessGrant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

final class DeliverManagementAccess
{
    public function execute(int $deliveryId): void
    {
        // scope-bypass: trusted queue carries only a delivery id; validate its tenant/family under lock before minting.
        $delivery = CustomerAccessDelivery::withoutGlobalScopes()->findOrFail($deliveryId);
        $mail = DB::transaction(function () use ($delivery): ?array {
            ClaimSlots::lockTenant((int) $delivery->idtenant);
            $delivery->refresh();
            if ($delivery->sent_at !== null || $delivery->attempts >= 3) {
                return null;
            }
            $snapshot = new CustomerAccessGrant([
                'idtenant' => $delivery->idtenant, 'payment_id' => $delivery->payment_id,
                'booking_id' => $delivery->booking_id, 'booking_ids' => $delivery->booking_ids,
                'family_version' => $delivery->family_version, 'profile' => AccessProfile::Management,
            ]);
            $context = app(LoadAccessSubject::class)->execute($snapshot);
            if ($delivery->source_grant_id !== null) {
                $source = app(ResolveCustomerAccess::class)->execute((int) $delivery->source_grant_id);
                abort_unless($source->allows(AccessOperation::Reschedule, $context->payment, $context->bookings->first()), 404);
            }
            abort_if(config('mail.default') === 'log', 503);
            $subject = $context->payment ?? $context->bookings->first();
            $issued = app(IssueCustomerAccess::class)->execute($subject, AccessProfile::Management, $delivery->recipient);
            $delivery->increment('attempts');
            $url = app(CustomerAccessLinks::class)->entry($issued);
            $message = $delivery->reason === 'rescheduled'
                ? new BookingRescheduledMail($context->bookings->first()->load(['tenant.user', 'layanan']), $url)
                : ($context->payment === null ? new BookingConfirmedMail($context->bookings->first()->load(['tenant.user', 'layanan']), $url) : new BookingGroupInvoiceMail($context->payment, $context->bookings->load(['tenant.user', 'layanan']), $url));

            return [$delivery->recipient, $message];
        });
        if ($mail === null) {
            return;
        }
        Mail::to($mail[0])->send($mail[1]);
        $delivery->update(['sent_at' => now()]);
    }
}
