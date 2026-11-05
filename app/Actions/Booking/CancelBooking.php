<?php

namespace App\Actions\Booking;

use App\Actions\CustomerAccess\ResolveCustomerAccess;
use App\Actions\Payment\PaymentLifecycle;
use App\Actions\Schedule\ClaimSlots;
use App\Domain\Booking\BookingRules;
use App\Domain\Booking\BookingState;
use App\Domain\CustomerAccess\AccessContext;
use App\Domain\CustomerAccess\AccessOperation;
use App\Domain\Payment\PaymentRules;
use App\Domain\Payment\PaymentState;
use App\Domain\Payment\PaymentTransition;
use App\Domain\Payment\RefundRequestState;
use App\Mail\BookingCancelledMail;
use App\Models\Booking;
use App\Models\BookingLog;
use App\Models\Payment;
use App\Models\RefundRequest;
use App\Models\Scopes\TenantScope;
use App\Support\TenantActor;
use App\Traits\ClearsBookingCache;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class CancelBooking
{
    use ClearsBookingCache;

    public function __construct(
        protected PaymentLifecycle $paymentLifecycle
    ) {}

    /**
     * Cancel a booking (shared between Customer and Owner).
     *
     * @param  string  $actor  'customer'|'owner'
     * @return array{success: bool, error?: string, booking?: Booking}
     */
    public function execute(Booking $booking, string $actor = 'customer', ?string $reason = null, ?AccessContext $access = null, bool $teamDecision = false, ?RefundRequest $refundRequest = null, ?RefundRequestState $refundDecision = null): array
    {
        try {
            $error = DB::transaction(function () use ($booking, $actor, $reason, $access, $teamDecision, $refundRequest, $refundDecision): ?string {
                // Tenant-first synchronization with claims and payment outcomes (ADR-0005 decision 4)
                ClaimSlots::lockTenant((int) $booking->idtenant);
                if ($access !== null) {
                    app(ResolveCustomerAccess::class)->underLock($access, AccessOperation::CancelBooking, $booking);
                }
                if ($actor === 'customer') {
                    return 'Booking yang sudah dibayar tidak dapat dibatalkan langsung. Ajukan refund atau hubungi pemilik.';
                }

                if ($actor === 'owner') {
                    $booking->refresh();
                    if (! BookingState::canTransition($booking->status, BookingState::STATUS_CANCELLED)) {
                        return 'Status booking sudah berubah.';
                    }
                    $payment = $booking->payment;
                    if ($booking->idpayment && (! $payment instanceof Payment || (int) $payment->idtenant !== (int) $booking->idtenant)) {
                        return 'Pembayaran booking tidak tersedia.';
                    }
                    if ($payment instanceof Payment && $payment->status === PaymentState::STATUS_PENDING) {
                        $holdEnd = BookingRules::onlinePaymentHoldEnd($booking, $payment);
                        if ($holdEnd !== null) {
                            return 'Menunggu pembayaran online, berakhir '.$holdEnd->setTimezone('Asia/Jakarta')->format('H:i').' WIB.';
                        }
                        if (blank($reason)) {
                            return 'Alasan pembatalan wajib diisi.';
                        }
                        $linked = PaymentRules::hasProviderLink($payment);
                        if ($linked || $teamDecision) {
                            $transition = $linked ? PaymentTransition::Expired : PaymentTransition::CancelledByBusiness;
                            if ($this->paymentLifecycle->transition($payment, $transition)->status() !== PaymentState::STATUS_GAGAL) {
                                return 'Pembayaran booking tidak dapat ditutup.';
                            }
                            $booking->refresh();
                        }
                    }
                }
                if (blank($reason)) {
                    return 'Alasan pembatalan wajib diisi.';
                }
                // If owner cancels a multi-slot booking, cancel all linked bookings for this payment group
                $targetBookings = ($actor === 'owner' && $booking->isMultiSlot() && $booking->idpayment)
                    // scope-bypass: payment group re-filtered by the booking's own idtenant
                    ? Booking::withoutGlobalScope(TenantScope::class)
                        ->where('idpayment', $booking->idpayment)
                        ->where('idtenant', $booking->idtenant)
                        ->get()
                    : collect([$booking]);

                if ($refundRequest !== null && ($booking->status !== BookingState::STATUS_PAID
                    || $targetBookings->contains(fn (Booking $member) => ! in_array($member->status, [BookingState::STATUS_PAID, BookingState::STATUS_CANCELLED], true)))) {
                    return 'Grup booking tidak dapat dibatalkan.';
                }

                abort_if($targetBookings->contains(fn (Booking $member) => ! BookingState::canTransition($member->status, BookingState::STATUS_CANCELLED)),
                    409, 'Grup booking tidak dapat dibatalkan seluruhnya.');

                foreach ($targetBookings as $target) {
                    if ($target->status !== BookingState::STATUS_CANCELLED && BookingState::canTransition($target->status, BookingState::STATUS_CANCELLED)) {
                        $target->update(['status' => BookingState::STATUS_CANCELLED]);

                        // Audit log per slot
                        BookingLog::record(
                            $target->id,
                            'cancelled',
                            $reason,
                            [
                                'actor' => $actor,
                                'cancelled_at' => now()->toIso8601String(),
                            ]
                        );

                        // Clear availability cache
                        $dateStr = $target->tanggalbooking instanceof Carbon
                            ? $target->tanggalbooking->toDateString()
                            : Carbon::parse($target->tanggalbooking)->toDateString();

                        DB::afterCommit(fn () => $this->clearBookingAvailabilityCache(
                            (int) $target->idtenant,
                            (int) $target->idlayanan,
                            $dateStr
                        ));
                    }
                }

                if ($booking->idpayment) {
                    $openRequest = $refundRequest ?? RefundRequest::where('idtenant', $booking->idtenant)
                        ->where('idpayment', $booking->idpayment)->where('status', RefundRequestState::Open->value)->first();
                    $refund = $this->paymentLifecycle->refundCancelledBooking(
                        $booking, $reason, businessCancellation: $refundDecision === null, request: $openRequest, decision: $refundDecision,
                    );
                    abort_if($refundRequest !== null && $refund === null, 409, 'Refund pembayaran tidak tersedia.');
                    if ($refund !== null && $refundRequest === null && $openRequest !== null) {
                        $openRequest->update([
                            'status' => RefundRequestState::AcceptedFull,
                            'decided_by' => app(TenantActor::class)->bookingMetadata('system')['actor_iduser'],
                            'decision_reason' => $reason, 'decided_at' => now(),
                        ]);
                    }
                }

                return null;
            });
            if ($error !== null) {
                return ['success' => false, 'error' => $error];
            }

            $announce = function () use ($booking): void {
                // 5. Send cancellation email to customer
                if ($booking->email) {
                    try {
                        $booking->refresh()->load(['tenant.user', 'layanan', 'payment', 'refund']);
                        Mail::to($booking->email)->send(new BookingCancelledMail($booking));
                    } catch (\Throwable $e) {
                        Log::warning('CancelBooking: Failed to send cancellation email', ['exception_type' => $e::class, 'booking_id' => $booking->id]);
                    }
                }

            };
            if ($teamDecision || $refundRequest !== null) {
                DB::afterCommit($announce);
            } else {
                $announce();
            }

            return ['success' => true, 'booking' => $booking];

        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('CancelBooking failed', [
                'booking_id' => $booking->id,
                'exception_type' => $e::class,
            ]);

            return [
                'success' => false,
                'error' => 'Terjadi kesalahan saat membatalkan booking. Silakan coba lagi.',
            ];
        }
    }
}
