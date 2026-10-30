<?php

namespace App\Actions\Booking;

use App\Actions\CustomerAccess\RequestManagementDelivery;
use App\Actions\Payment\PaymentLifecycle;
use App\Actions\Schedule\ClaimSlots;
use App\Actions\Schedule\SlotClaimRefused;
use App\Domain\Booking\BookingState;
use App\Domain\Subscription\SubscriptionState;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Traits\ClearsBookingCache;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateWalkInBooking
{
    use ClearsBookingCache;

    /**
     * Execute walk-in booking creation by owner.
     *
     * @param array{
     *     idschedule: int,
     *     namapelanggan: string,
     *     nomorhp: string,
     *     email?: ?string,
     *     catatan?: ?string,
     *     metode?: ?string
     * } $data
     */
    public function execute(Tenant $tenant, array $data): ?Booking
    {
        $subscription = Subscription::where('idtenant', $tenant->id)->latest()->first();
        if ($subscription !== null && SubscriptionState::isExpired($subscription)) {
            throw ValidationException::withMessages([
                'error' => 'Langganan Anda telah habis, mohon perpanjang untuk menambah booking walk-in.',
            ]);
        }

        try {
            $booking = DB::transaction(function () use ($tenant, $data) {
                // scope-bypass: only to read the schedule's service; ClaimSlots verifies tenant ownership under lock.
                $serviceId = Schedule::withoutGlobalScopes()->whereKey($data['idschedule'])->value('idlayanan');

                if ($serviceId === null) {
                    return null;
                }

                // Owner walk-ins use the same claim as customer bookings (ADR-0005 decision 2)
                $schedule = app(ClaimSlots::class)
                    ->execute($tenant, (int) $serviceId, [(int) $data['idschedule']])
                    ->first();

                $service = $schedule->layanan;
                $amount = ($schedule->harga_override !== null && $schedule->harga_override !== '')
                    ? (float) $schedule->harga_override
                    : ($service ? (float) $service->harga : 0);

                // Create Payment record for Walk-in
                $orderId = 'WLK-'.strtoupper(Str::random(10));
                $payment = app(PaymentLifecycle::class)->recordSettled([
                    'idtenant' => $tenant->id,
                    'tipe' => 'booking',
                    'provider' => 'manual',

                    'jumlah' => $amount,
                    'metode' => $data['metode'] ?? 'cash',
                    'order_id' => $orderId,
                    'nama_pembayar' => $data['namapelanggan'],
                    'email_pembayar' => $data['email'] ?? ($tenant->user->email ?? 'walkin@example.com'),
                    'hp_pembayar' => $data['nomorhp'],
                ]);

                // Create Booking record
                $booking = Booking::create([
                    'idtenant' => $tenant->id,
                    'idlayanan' => $schedule->idlayanan,
                    'idschedule' => $schedule->id,
                    'idpayment' => $payment->id,
                    'namapelanggan' => $data['namapelanggan'],
                    'nomorhp' => $data['nomorhp'],
                    'email' => $data['email'] ?? null,
                    'tanggalbooking' => $schedule->tanggal,
                    'jam' => $schedule->jam_mulai,
                    'status' => BookingState::STATUS_PAID,
                    'catatan' => $data['catatan'] ?? 'Walk-in booking via Owner Calendar',
                ]);

                $booking->assignBookingCode();
                if ($booking->email) {
                    app(RequestManagementDelivery::class)->execute($payment, 'confirmed');
                }

                return $booking;
            });
        } catch (SlotClaimRefused) {
            $booking = null;
        }

        if (! $booking) {
            return null;
        }

        $dateStr = $booking->tanggalbooking instanceof Carbon
            ? $booking->tanggalbooking->toDateString()
            : Carbon::parse($booking->tanggalbooking)->toDateString();

        $this->clearBookingAvailabilityCache(
            (int) $booking->idtenant,
            (int) $booking->idlayanan,
            $dateStr
        );

        return $booking;
    }
}
