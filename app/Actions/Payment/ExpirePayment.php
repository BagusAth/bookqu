<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Domain\Payment\PaymentState;
use App\Models\Booking;
use App\Models\Payment;
use App\Support\TenantContext;
use App\Traits\ClearsBookingCache;
use Illuminate\Support\Facades\DB;

class ExpirePayment
{
    use ClearsBookingCache;

    /**
     * Atomically expire a pending payment and cancel its associated pending bookings.
     *
     * @param Payment $payment
     * @return array<string, mixed>
     */
    public function execute(Payment $payment): array
    {
        return DB::transaction(function () use ($payment) {
            /** @var Payment|null $lockedPayment */
            $lockedPayment = Payment::withoutGlobalScopes()->lockForUpdate()->find($payment->id);
            if (!$lockedPayment) {
                return [
                    'status'  => 'not_found',
                    'message' => 'Payment tidak ditemukan.',
                ];
            }

            $payment = $lockedPayment;
            app(TenantContext::class)->setTenantId($payment->idtenant);

            if ($payment->status === PaymentState::STATUS_SUKSES) {
                return [
                    'status'  => PaymentState::STATUS_SUKSES,
                    'message' => 'Payment sudah berstatus sukses, pembatalan diabaikan.',
                    'payment' => $payment,
                ];
            }

            if ($payment->status === PaymentState::STATUS_PENDING) {
                $payment->update(['status' => PaymentState::STATUS_GAGAL]);
            }

            $cancelledBookings = [];
            if ($payment->tipe === PaymentState::TIPE_BOOKING) {
                $bookings = Booking::withoutGlobalScopes()
                    ->where('idpayment', $payment->id)
                    ->lockForUpdate()
                    ->get();

                foreach ($bookings as $booking) {
                    if ($booking->status === 'pending') {
                        $booking->update(['status' => 'cancelled']);
                        $cancelledBookings[] = $booking;
                    }
                }

                if (!empty($cancelledBookings)) {
                    DB::afterCommit(function () use ($cancelledBookings) {
                        foreach ($cancelledBookings as $booking) {
                            if ($booking->idlayanan && $booking->tanggalbooking) {
                                $tanggal = is_string($booking->tanggalbooking)
                                    ? $booking->tanggalbooking
                                    : $booking->tanggalbooking->format('Y-m-d');
                                $this->clearScheduleCache($booking->idtenant, $booking->idlayanan, [$tanggal]);
                                $this->clearAvailabilityCache($booking->idtenant, $booking->idlayanan);
                            }
                        }
                    });
                }
            }

            return [
                'status'             => PaymentState::STATUS_GAGAL,
                'transaction_status' => 'expire',
                'message'            => 'Payment kadaluarsa dan booking berhasil dibatalkan.',
                'payment'            => $payment,
            ];
        });
    }
}
