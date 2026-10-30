<?php

declare(strict_types=1);

namespace App\Actions\Payment\Lifecycle;

use App\Domain\Booking\BookingState;
use App\Domain\Payment\PaymentTransition;
use App\Mail\BookingGroupInvoiceMail;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\UsageLog;
use App\Models\User;
use App\Notifications\NewBookingOwnerNotification;
use App\Traits\ClearsBookingCache;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Booking-group adapter: a booking payment pays for every booking that carries its id.
 */
class BookingGroupConsequences implements PaymentConsequences
{
    use ClearsBookingCache;

    public function accepts(PaymentTransition $transition): bool
    {
        return true;
    }

    public function canDeliver(Payment $payment): bool
    {
        return ! $this->lockGroup($payment)
            ->contains(fn (Booking $booking) => $booking->status === BookingState::STATUS_CANCELLED);
    }

    public function deliver(Payment $payment, PaymentTransition $transition): void
    {
        $paidBookings = [];

        foreach ($this->lockGroup($payment) as $booking) {
            if ($booking->status === BookingState::STATUS_PENDING) {
                $booking->update(['status' => BookingState::STATUS_PAID]);

                if (! $booking->booking_code) {
                    $booking->assignManagementTokens();
                    $booking->refresh();
                }

                if ($transition === PaymentTransition::ProviderSucceeded) {
                    try {
                        UsageLog::record($booking->idtenant, 'booking');
                    } catch (\Exception $e) {
                        Log::error('Gagal catat usage log booking: '.$e->getMessage());
                    }
                }

                $paidBookings[] = $booking;
            } elseif ($booking->status === BookingState::STATUS_PAID) {
                $paidBookings[] = $booking;
            }
        }

        if ($paidBookings === []) {
            return;
        }

        DB::afterCommit(function () use ($paidBookings, $payment, $transition) {
            if ($transition === PaymentTransition::ProviderSucceeded) {
                $this->announce($payment, $paidBookings);
            }

            $this->clearCaches($paidBookings);
        });
    }

    public function withdraw(Payment $payment, PaymentTransition $transition): void
    {
        $cancelledBookings = [];

        foreach ($this->lockGroup($payment) as $booking) {
            if ($booking->status === BookingState::STATUS_PENDING) {
                $booking->update(['status' => BookingState::STATUS_CANCELLED]);
                $cancelledBookings[] = $booking;
            }
        }

        if ($cancelledBookings !== []) {
            DB::afterCommit(fn () => $this->clearCaches($cancelledBookings));
        }
    }

    public function refundBookingId(Payment $payment): ?int
    {
        $bookingId = $this->lockGroup($payment)->first()?->id;

        return $bookingId === null ? null : (int) $bookingId;
    }

    /**
     * Lock the bookings of the payment group. Bookings of another tenant that point at
     * this payment are an anomaly: they are logged and left untouched.
     *
     * @return Collection<int, Booking>
     */
    private function lockGroup(Payment $payment): Collection
    {
        // scope-bypass: the group is resolved by payment id; bookings of another tenant are filtered out below.
        $bookings = Booking::withoutGlobalScopes()
            ->with(['layanan', 'tenant.user', 'payment'])
            ->where('idpayment', $payment->id)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $foreign = $bookings->filter(fn (Booking $booking) => (int) $booking->idtenant !== (int) $payment->idtenant);

        if ($foreign->isNotEmpty()) {
            Log::warning('PaymentLifecycle: booking of another tenant attached to payment group', [
                'payment_id' => $payment->id,
                'booking_ids' => $foreign->pluck('id')->all(),
            ]);
        }

        return $bookings->reject(fn (Booking $booking) => (int) $booking->idtenant !== (int) $payment->idtenant)->values();
    }

    /**
     * One owner notification and one customer invoice per payment group.
     *
     * @param  array<int, Booking>  $paidBookings
     */
    private function announce(Payment $payment, array $paidBookings): void
    {
        $firstBooking = $paidBookings[0];

        $tenant = $firstBooking->tenant;
        $owner = $tenant instanceof Tenant ? $tenant->user : null;
        if ($owner instanceof User && $owner->email) {
            try {
                $owner->notify(new NewBookingOwnerNotification($firstBooking));
            } catch (\Exception $e) {
                Log::error('Gagal kirim notif booking ke owner: '.$e->getMessage());
            }
        }

        $recipientEmail = $firstBooking->email ?: $payment->email_pembayar;
        if ($recipientEmail) {
            try {
                Mail::to($recipientEmail)
                    ->send(new BookingGroupInvoiceMail($payment, collect($paidBookings), $payment->getManageUrl()));
            } catch (\Exception $e) {
                Log::error('Gagal kirim email invoice grup ke pelanggan: '.$e->getMessage());
            }
        }
    }

    /**
     * @param  array<int, Booking>  $bookings
     */
    private function clearCaches(array $bookings): void
    {
        foreach ($bookings as $booking) {
            if ($booking->idlayanan && $booking->tanggalbooking) {
                $tanggal = Carbon::parse($booking->tanggalbooking)->toDateString();
                $this->clearScheduleCache($booking->idtenant, $booking->idlayanan, [$tanggal]);
                $this->clearAvailabilityCache($booking->idtenant, $booking->idlayanan);
            }
        }
    }
}
