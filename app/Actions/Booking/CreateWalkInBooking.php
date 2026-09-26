<?php

namespace App\Actions\Booking;

use App\Domain\Booking\BookingRules;
use App\Domain\Booking\BookingState;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Tenant;
use App\Traits\ClearsBookingCache;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateWalkInBooking
{
    use ClearsBookingCache;

    /**
     * Execute walk-in booking creation by owner.
     *
     * @param Tenant $tenant
     * @param array{
     *     idschedule: int,
     *     namapelanggan: string,
     *     nomorhp: string,
     *     email?: ?string,
     *     catatan?: ?string,
     *     metode?: ?string
     * } $data
     * @return Booking|null
     */
    public function execute(Tenant $tenant, array $data): ?Booking
    {
        $booking = DB::transaction(function () use ($tenant, $data) {
            $schedule = Schedule::where('id', $data['idschedule'])
                ->where('idtenant', $tenant->id)
                ->where('status', 'tersedia')
                ->lockForUpdate()
                ->first();

            if (!$schedule) {
                return null;
            }

            // Check if slot already has an active booking via domain rule
            if (BookingRules::isSlotOccupied((int) $schedule->id)) {
                return null;
            }

            $service = $schedule->layanan;
            $amount = ($schedule->harga_override !== null && $schedule->harga_override !== '')
                ? (float) $schedule->harga_override
                : ($service ? (float) $service->harga : 0);

            // Create Payment record for Walk-in
            $orderId = 'WLK-' . strtoupper(Str::random(10));
            $payment = Payment::create([
                'idtenant'       => $tenant->id,
                'tipe'           => 'booking',
                'jumlah'         => $amount,
                'status'         => 'sukses',
                'metode'         => $data['metode'] ?? 'cash',
                'order_id'       => $orderId,
                'nama_pembayar'  => $data['namapelanggan'],
                'email_pembayar' => $data['email'] ?? ($tenant->user->email ?? 'walkin@example.com'),
                'hp_pembayar'    => $data['nomorhp'],
            ]);

            // Create Booking record
            $booking = Booking::create([
                'idtenant'       => $tenant->id,
                'idlayanan'      => $schedule->idlayanan,
                'idschedule'     => $schedule->id,
                'idpayment'      => $payment->id,
                'namapelanggan'  => $data['namapelanggan'],
                'nomorhp'        => $data['nomorhp'],
                'email'          => $data['email'] ?? null,
                'tanggalbooking' => $schedule->tanggal,
                'jam'            => $schedule->jam_mulai,
                'status'         => BookingState::STATUS_PAID,
                'catatan'        => $data['catatan'] ?? 'Walk-in booking via Owner Calendar',
            ]);

            $booking->assignManagementTokens();

            return $booking;
        });

        if (!$booking) {
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
