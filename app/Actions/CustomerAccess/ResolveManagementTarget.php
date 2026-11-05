<?php

namespace App\Actions\CustomerAccess;

use App\Domain\CustomerAccess\AccessContext;
use App\Domain\CustomerAccess\AccessOperation;
use App\Models\Booking;
use App\Models\Payment;

final class ResolveManagementTarget
{
    public function payment(AccessContext $context, string $orderId): Payment
    {
        abort_unless($context->payment !== null, 404);
        abort_unless($context->allows(AccessOperation::Manage, $context->payment), 404);
        abort_unless($context->payment->order_id === $orderId, 404);
        $payment = $context->payment;
        $payment->setRelation('bookings', $context->bookings->load(['layanan', 'schedule', 'tenant.user', 'review']));
        $payment->load('tenant');

        return $payment;
    }

    public function booking(AccessContext $context, string $bookingCode, AccessOperation $operation): Booking
    {
        $booking = $context->bookings->firstWhere('booking_code', $bookingCode);
        abort_unless($booking instanceof Booking, 404);
        abort_unless($context->allows($operation, booking: $booking), 404);
        $booking->load(['tenant.user', 'layanan', 'payment', 'logs', 'refund', 'review']);

        return $booking;
    }
}
