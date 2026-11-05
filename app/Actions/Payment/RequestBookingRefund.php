<?php

namespace App\Actions\Payment;

use App\Actions\CustomerAccess\ResolveCustomerAccess;
use App\Domain\Booking\BookingRules;
use App\Domain\CustomerAccess\AccessContext;
use App\Domain\CustomerAccess\AccessOperation;
use App\Domain\Payment\PaymentRules;
use App\Domain\Payment\PaymentState;
use App\Domain\Payment\RefundRequestState;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\RefundRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RequestBookingRefund
{
    public function __construct(private ResolveCustomerAccess $access, private NotifyRefundTeam $notify) {}

    /** @param array{reason: string, bank_nama: string, nomor_rekening: string, nama_pemilik_rekening: string} $data */
    public function execute(Booking $booking, AccessContext $grant, #[\SensitiveParameter] array $data): void
    {
        DB::transaction(function () use ($booking, $grant, $data): void {
            $context = $this->access->underLock($grant, AccessOperation::CancelBooking, $booking);
            $booking->refresh();
            $payment = $context->payment;
            if (! $payment instanceof Payment || (int) $booking->idpayment !== $payment->id
                || $payment->status !== PaymentState::STATUS_SUKSES || PaymentRules::providerOf($payment) === 'manual'
                || (float) $payment->jumlah <= 0 || ! BookingRules::canRequestRefund($booking)) {
                throw ValidationException::withMessages(['cancel' => 'Permintaan refund tidak tersedia. Hubungi pemilik.']);
            }
            foreach ($context->bookings as $member) {
                if (! BookingRules::canRequestRefund($member)) {
                    throw ValidationException::withMessages(['cancel' => 'Batas pengajuan refund untuk grup booking ini sudah lewat.']);
                }
            }

            if (RefundRequest::where('idtenant', $booking->idtenant)->where('idpayment', $payment->id)
                ->where('status', RefundRequestState::Open->value)->exists() || Refund::where('payment_id', $payment->id)->exists()) {
                throw ValidationException::withMessages(['cancel' => 'Refund untuk pembayaran ini sudah diajukan.']);
            }

            $request = RefundRequest::create($data + [
                'idtenant' => $booking->idtenant, 'idpayment' => $payment->id,
                'booking_id' => $booking->id, 'status' => RefundRequestState::Open,
            ]);
            DB::afterCommit(fn () => $this->notify->execute((int) $request->idtenant, $request->id, 'refund_request'));
        });
    }
}
