<?php

namespace App\Actions\Payment;

use App\Domain\Booking\BookingRules;
use App\Domain\CustomerAccess\AccessContext;
use App\Domain\CustomerAccess\AccessOperation;
use App\Domain\Payment\PaymentRules;
use App\Domain\Payment\PaymentState;
use App\Domain\Payment\RefundRequestState;
use App\Models\Booking;
use App\Models\Refund;
use App\Models\RefundRequest;
use App\Support\StatusBadge;

final class GetCustomerRefundForm
{
    /** @return array{refundBookingCode: ?string, refundRequestStatus: ?string} */
    public function execute(AccessContext $access, ?Booking $booking): array
    {
        $data = ['refundBookingCode' => null, 'refundRequestStatus' => null];
        $payment = $access->payment;
        if ($booking === null || $payment === null || ! $access->allows(AccessOperation::CancelBooking, $payment, $booking)) {
            return $data;
        }

        $refund = Refund::where('payment_id', $payment->id)->first();
        $request = RefundRequest::where('idtenant', $access->grant->idtenant)->where('idpayment', $payment->id)->latest('id')->first();
        if ($refund !== null) {
            $data['refundRequestStatus'] = StatusBadge::for('refund', $refund->status)[0];
        } elseif ($request !== null) {
            $data['refundRequestStatus'] = StatusBadge::for('refund_request', $request->status->value)[0];
        }

        if ($refund === null && $request?->status !== RefundRequestState::Open && $payment->status === PaymentState::STATUS_SUKSES
            && PaymentRules::providerOf($payment) !== 'manual' && (float) $payment->jumlah > 0
            && $access->bookings->every(BookingRules::canRequestRefund(...))) {
            $data['refundBookingCode'] = $booking->booking_code;
        }

        return $data;
    }
}
