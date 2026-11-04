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
use App\Mail\BookingCancelledMail;
use App\Models\Booking;
use App\Models\BookingLog;
use App\Models\Payment;
use App\Models\Scopes\TenantScope;
use App\Notifications\BookingStatusChangedOwnerNotification;
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
    public function execute(Booking $booking, string $actor = 'customer', ?string $reason = null, ?AccessContext $access = null, bool $teamDecision = false): array
    {
        // Guard 1: customer cannot cancel individual slot of a multi-slot booking
        if ($actor === 'customer' && $booking->isMultiSlot()) {
            return [
                'success' => false,
                'error' => 'Booking multi-slot tidak dapat dibatalkan per slot secara individual. Silakan hubungi pengelola bisnis.',
            ];
        }

        // Guard 2: check valid status transition
        if (! BookingState::canTransition($booking->status, BookingState::STATUS_CANCELLED)) {
            $msg = $actor === 'owner'
                ? "Status tidak dapat diubah dari '{$booking->status}' ke 'cancelled'."
                : "Booking ini tidak dapat dibatalkan (status: {$booking->status}).";

            return ['success' => false, 'error' => $msg];
        }

        // Guard 3: Customer policy check (minimal X hours before slot)
        if ($actor === 'customer' && ! BookingRules::canCancel($booking)) {
            $cancelHours = $booking->tenant?->cancel_before_hours ?? 24;

            return [
                'success' => false,
                'error' => "Booking tidak dapat dibatalkan. Pembatalan hanya diizinkan minimal {$cancelHours} jam sebelum jadwal.",
            ];
        }

        try {
            $error = DB::transaction(function () use ($booking, $actor, $reason, $access, $teamDecision): ?string {
                // Tenant-first synchronization with claims and payment outcomes (ADR-0005 decision 4)
                ClaimSlots::lockTenant((int) $booking->idtenant);
                if ($access !== null) {
                    app(ResolveCustomerAccess::class)->underLock($access, AccessOperation::CancelBooking, $booking);
                    $booking->refresh();
                    abort_unless(! $booking->isMultiSlot() && BookingRules::canCancel($booking), 422);
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
                // If owner cancels a multi-slot booking, cancel all linked bookings for this payment group
                $targetBookings = ($actor === 'owner' && $booking->isMultiSlot() && $booking->idpayment)
                    // scope-bypass: payment group re-filtered by the booking's own idtenant
                    ? Booking::withoutGlobalScope(TenantScope::class)
                        ->where('idpayment', $booking->idpayment)
                        ->where('idtenant', $booking->idtenant)
                        ->get()
                    : collect([$booking]);

                foreach ($targetBookings as $target) {
                    if ($target->status !== BookingState::STATUS_CANCELLED && BookingState::canTransition($target->status, BookingState::STATUS_CANCELLED)) {
                        $target->update(['status' => BookingState::STATUS_CANCELLED]);

                        // Audit log per slot
                        BookingLog::record(
                            $target->id,
                            'cancelled',
                            $reason ?? ($actor === 'owner' ? 'Booking dibatalkan oleh owner.' : 'Booking dibatalkan oleh customer.'),
                            [
                                'actor' => $actor,
                                'cancelled_at' => now()->toIso8601String(),
                            ]
                        );

                        // Clear availability cache
                        $dateStr = $target->tanggalbooking instanceof Carbon
                            ? $target->tanggalbooking->toDateString()
                            : Carbon::parse($target->tanggalbooking)->toDateString();

                        $this->clearBookingAvailabilityCache(
                            (int) $target->idtenant,
                            (int) $target->idlayanan,
                            $dateStr
                        );
                    }
                }

                // A customer cancelling a paid booking is owed one refund (ADR-0004 decision 7)
                if ($actor === 'customer' || $teamDecision) {
                    $this->paymentLifecycle->refundCancelledBooking($booking, $reason, $teamDecision);
                }

                return null;
            });
            if ($error !== null) {
                return ['success' => false, 'error' => $error];
            }

            $announce = function () use ($booking, $actor): void {
                // 5. Send cancellation email to customer
                if ($booking->email) {
                    try {
                        $booking->refresh()->load(['tenant.user', 'layanan', 'payment', 'refund']);
                        Mail::to($booking->email)->send(new BookingCancelledMail($booking));
                    } catch (\Throwable $e) {
                        Log::warning('CancelBooking: Failed to send cancellation email', ['exception_type' => $e::class, 'booking_id' => $booking->id]);
                    }
                }

                // 6. Notify owner (only if cancelled by customer)
                if ($actor !== 'owner') {
                    try {
                        $owner = $booking->tenant?->user;
                        if ($owner) {
                            $owner->notify(new BookingStatusChangedOwnerNotification(
                                $booking,
                                BookingState::STATUS_CANCELLED,
                                ['updated_by' => $actor]
                            ));
                        }
                    } catch (\Throwable $e) {
                        Log::warning('CancelBooking: Failed to notify owner', ['exception_type' => $e::class, 'booking_id' => $booking->id]);
                    }
                }
            };
            if ($teamDecision) {
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
