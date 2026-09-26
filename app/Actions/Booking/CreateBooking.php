<?php

namespace App\Actions\Booking;

use App\Domain\Booking\BookingRules;
use App\Domain\Booking\BookingState;
use App\Mail\BookingGroupInvoiceMail;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Notifications\NewBookingOwnerNotification;
use App\Traits\ClearsBookingCache;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Midtrans\Snap;

class CreateBooking
{
    use ClearsBookingCache;

    /**
     * Execute the customer booking checkout workflow.
     *
     * @param Tenant $tenant
     * @param Service $service
     * @param string $selectedDate
     * @param array $selectedTimes
     * @param array $scheduleIds
     * @param array{namapelanggan: string, nomorhp: string, email: string, catatan: ?string} $customerData
     * @return array
     */
    public function execute(
        Tenant $tenant,
        Service $service,
        string $selectedDate,
        array $selectedTimes,
        array $scheduleIds,
        array $customerData
    ): array {
        $slotCount = count($scheduleIds);

        // Wrap slot availability check, atomic quota check, and booking/payment creation in a transaction
        $result = DB::transaction(function () use ($tenant, $service, $selectedDate, $selectedTimes, $scheduleIds, $customerData, $slotCount) {
            $wib = 'Asia/Jakarta';
            $nowWib = Carbon::now($wib);

            // 1. Atomic monthly booking quota check inside transaction: lock stable synchronization row (Subscription or Tenant)
            $subscription = Subscription::with('plan')
                ->where('idtenant', $tenant->id)
                ->latest()
                ->lockForUpdate()
                ->first();

            if (!$subscription) {
                DB::table('tenants')->where('id', $tenant->id)->lockForUpdate()->first();
            }

            $isUnlimitedBooking = ($subscription && $subscription->status === 'trial')
                || ($subscription?->plan?->isunlimited ?? false)
                || (($subscription?->plan?->namapaket ?? '') === 'pro')
                || (($subscription?->plan?->maxbooking ?? 0) <= 0);

            if (!$isUnlimitedBooking && ($subscription?->plan?->maxbooking ?? 0) > 0) {
                $dateCarbon = Carbon::parse($selectedDate, $wib);
                $totalMonthlyBookings = DB::table('bookings')
                    ->where('idtenant', $tenant->id)
                    ->whereYear('tanggalbooking', $dateCarbon->year)
                    ->whereMonth('tanggalbooking', $dateCarbon->month)
                    ->whereIn('status', [BookingState::STATUS_PENDING, BookingState::STATUS_PAID, BookingState::STATUS_COMPLETED])
                    ->count();

                if (($totalMonthlyBookings + $slotCount) > $subscription->plan->maxbooking) {
                    return [
                        'quota_error' => true,
                        'error'       => 'Kapasitas kuota booking bulanan bisnis ini tidak mencukupi (tersisa ' . max(0, $subscription->plan->maxbooking - $totalMonthlyBookings) . ' dari ' . $subscription->plan->maxbooking . '). Silakan kurangi jumlah slot atau hubungi pemilik bisnis.',
                    ];
                }
            }

            // 2. Lock all selected schedules
            $lockedSchedules = DB::table('schedules')
                ->whereIn('id', $scheduleIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($lockedSchedules->count() !== $slotCount) {
                return ['error' => 'Satu atau lebih jadwal tidak ditemukan atau tidak tersedia.'];
            }

            $orderedSchedules = [];
            foreach ($scheduleIds as $sid) {
                $schedule = $lockedSchedules->get($sid);
                if (!$schedule) {
                    return ['error' => 'Jadwal tidak ditemukan.'];
                }

                if ((int) $schedule->idtenant !== (int) $tenant->id) {
                    return ['error' => 'Jadwal tidak sesuai dengan unit bisnis terpilih.'];
                }

                if ((int) $schedule->idlayanan !== (int) $service->id) {
                    return ['error' => 'Jadwal tidak sesuai dengan paket layanan terpilih.'];
                }

                $schedDate = Carbon::parse($schedule->tanggal, $wib)->toDateString();
                if ($schedDate !== $selectedDate) {
                    return ['error' => 'Tanggal jadwal tidak sesuai dengan tanggal pemesanan.'];
                }

                if ($schedule->status !== 'tersedia') {
                    return ['error' => "Jadwal untuk jam {$schedule->jam_mulai} tidak tersedia."];
                }

                $slotDateTime = Carbon::parse($schedDate . ' ' . $schedule->jam_mulai, $wib);
                if ($slotDateTime->lessThanOrEqualTo($nowWib)) {
                    return ['error' => "Waktu sesi {$schedule->jam_mulai} sudah terlewat (WIB). Silakan pilih waktu lain."];
                }

                // Check if slot already has an active booking via domain rule
                if (BookingRules::isSlotOccupied((int) $schedule->id)) {
                    return ['error' => "Slot waktu {$schedule->jam_mulai} sudah dibooking oleh pelanggan lain. Silakan pilih waktu lain."];
                }

                $orderedSchedules[] = $schedule;
            }

            // 3. Contiguous validation: Sort schedules by jam_mulai ascending
            usort($orderedSchedules, fn($a, $b) => strcmp($a->jam_mulai, $b->jam_mulai));
            $durationMinutes = (int) $service->durasi;

            if (!BookingRules::validateContiguousSlots($orderedSchedules, $durationMinutes, $selectedDate, $wib)) {
                return ['error' => 'Slot harus berurutan tanpa jeda.'];
            }

            // 4. Calculate slot prices via domain rule
            $pricing = BookingRules::calculateSlotPrices($orderedSchedules, $service);
            $hargaAkhir = $pricing['total'];

            $fullCatatan = trim($customerData['catatan'] ?? '');
            if ($slotCount > 1) {
                $timeList = implode(', ', array_map(fn($s) => substr($s->jam_mulai, 0, 5), $orderedSchedules));
                $fullCatatan .= ($fullCatatan ? ' | ' : '') . "Multi-slot ({$slotCount}x): {$timeList}";
            }

            // 5. Free booking path ($0)
            if ($hargaAkhir <= 0) {
                $orderId = 'FREE-' . $tenant->id . '-' . time() . '-' . rand(100, 999);
                $payment = Payment::create([
                    'idtenant'       => $tenant->id,
                    'tipe'           => 'booking',
                    'jumlah'         => 0,
                    'status'         => 'sukses',
                    'metode'         => 'gratis',
                    'order_id'       => $orderId,
                    'manage_token'   => Booking::generateSecureToken(),
                    'nama_pembayar'  => $customerData['namapelanggan'],
                    'email_pembayar' => $customerData['email'],
                    'hp_pembayar'    => $customerData['nomorhp'],
                    'catatan'        => $fullCatatan ?: null,
                ]);

                $createdBookings = [];
                foreach ($orderedSchedules as $schedule) {
                    $slotTime = Carbon::parse($selectedDate . ' ' . $schedule->jam_mulai, $wib)->format('H:i');
                    $bk = Booking::create([
                        'idtenant'           => $tenant->id,
                        'idlayanan'          => $service->id,
                        'idschedule'         => $schedule->id,
                        'namapelanggan'      => $customerData['namapelanggan'],
                        'nomorhp'            => $customerData['nomorhp'],
                        'email'              => $customerData['email'],
                        'tanggalbooking'     => $selectedDate,
                        'jam'                => $slotTime . ':00',
                        'status'             => BookingState::STATUS_PAID,
                        'idpayment'          => $payment->id,
                        'booking_code'       => Booking::generateBookingCode(),
                        'cancellation_token' => Booking::generateSecureToken(),
                        'reschedule_token'   => Booking::generateSecureToken(),
                        'catatan'            => $fullCatatan ?: null,
                    ]);
                    $createdBookings[] = $bk;
                }

                if (!empty($createdBookings)) {
                    $payment->update(['idbooking' => $createdBookings[0]->id]);
                }

                return [
                    'free'       => true,
                    'payment'    => $payment,
                    'bookings'   => $createdBookings,
                    'schedules'  => $orderedSchedules,
                    'hargaAkhir' => 0,
                ];
            }

            // 6. Paid booking path: create single payment and N bookings with status 'pending'
            $orderId = 'BKG-' . $tenant->id . '-' . time() . '-' . rand(100, 999);

            $payment = Payment::create([
                'idtenant'       => $tenant->id,
                'tipe'           => 'booking',
                'jumlah'         => $hargaAkhir,
                'status'         => BookingState::STATUS_PENDING,
                'metode'         => 'midtrans',
                'order_id'       => $orderId,
                'manage_token'   => Booking::generateSecureToken(),
                'expired_at'     => now()->addMinutes(15),
                'nama_pembayar'  => $customerData['namapelanggan'],
                'email_pembayar' => $customerData['email'],
                'hp_pembayar'    => $customerData['nomorhp'],
                'catatan'        => $fullCatatan ?: null,
            ]);

            $createdBookings = [];
            foreach ($orderedSchedules as $schedule) {
                $slotTime = Carbon::parse($selectedDate . ' ' . $schedule->jam_mulai, $wib)->format('H:i');
                $newBooking = Booking::create([
                    'idtenant'           => $tenant->id,
                    'idlayanan'          => $service->id,
                    'idschedule'         => $schedule->id,
                    'namapelanggan'      => $customerData['namapelanggan'],
                    'nomorhp'            => $customerData['nomorhp'],
                    'email'              => $customerData['email'],
                    'tanggalbooking'     => $selectedDate,
                    'jam'                => $slotTime . ':00',
                    'status'             => BookingState::STATUS_PENDING,
                    'idpayment'          => $payment->id,
                    'booking_code'       => Booking::generateBookingCode(),
                    'cancellation_token' => Booking::generateSecureToken(),
                    'reschedule_token'   => Booking::generateSecureToken(),
                    'catatan'            => $fullCatatan ?: null,
                ]);
                $createdBookings[] = $newBooking;
            }

            if (!empty($createdBookings)) {
                $payment->update(['idbooking' => $createdBookings[0]->id]);
            }

            return [
                'free'       => false,
                'bookings'   => $createdBookings,
                'payment'    => $payment,
                'schedules'  => $orderedSchedules,
                'orderId'    => $orderId,
                'hargaAkhir' => $hargaAkhir,
            ];
        });

        if (isset($result['error'])) {
            return $result;
        }

        // Free booking post-processing: notifications & cache
        if ($result['free']) {
            $payment = $result['payment'];
            $bookingsList = collect($result['bookings']);
            $firstBooking = $bookingsList->first();

            if ($firstBooking) {
                $firstBooking->load(['tenant.user', 'layanan', 'payment']);

                $owner = $firstBooking->tenant?->user;
                if ($owner) {
                    try {
                        $owner->notify(new NewBookingOwnerNotification($firstBooking));
                    } catch (Exception $e) {
                        Log::error('Gagal kirim notif free booking ke owner: ' . $e->getMessage());
                    }
                }

                $recipientEmail = $firstBooking->email ?: $payment->email_pembayar;
                if ($recipientEmail) {
                    try {
                        $manageUrl = $payment->getManageUrl();
                        Mail::to($recipientEmail)
                            ->send(new BookingGroupInvoiceMail($payment, $bookingsList, $manageUrl));
                    } catch (Exception $e) {
                        Log::error('Gagal kirim email invoice grup free booking: ' . $e->getMessage());
                    }
                }
            }

            $this->clearBookingAvailabilityCache(
                $tenant->id,
                $service->id,
                Carbon::parse($selectedDate)->toDateString()
            );

            return $result;
        }

        // Paid booking post-processing: Snap token & cache
        $payment    = $result['payment'];
        $bookings   = $result['bookings'];
        $hargaAkhir = $result['hargaAkhir'];
        $orderId    = $result['orderId'];

        $itemName = 'Booking: ' . $service->namalayanan;
        if (count($bookings) > 1) {
            $itemName .= ' (' . count($bookings) . ' slot)';
        }

        $params = [
            'transaction_details' => [
                'order_id'     => $orderId,
                'gross_amount' => (int) $hargaAkhir,
            ],
            'customer_details' => [
                'first_name' => $customerData['namapelanggan'],
                'email'      => $customerData['email'],
                'phone'      => $customerData['nomorhp'],
            ],
            'item_details' => [
                [
                    'id'       => 'SRV-' . $service->id,
                    'price'    => (int) $hargaAkhir,
                    'quantity' => 1,
                    'name'     => $itemName,
                ],
            ],
            'expiry' => [
                'start_time' => now()->format('Y-m-d H:i:s O'),
                'unit'       => 'minute',
                'duration'   => 15,
            ],
        ];

        try {
            $snapToken = app()->environment('testing')
                ? 'mocked-snap-token'
                : Snap::getSnapToken($params);
            $payment->update(['snap_token' => $snapToken]);
        } catch (Exception $e) {
            Log::error('Midtrans Snap Error (Booking): ' . $e->getMessage());
            // Snap token failed — cancel payment and all bookings, release slots
            DB::transaction(function () use ($payment, $bookings) {
                $payment->update(['status' => 'gagal']);
                foreach ($bookings as $bk) {
                    $bk->update(['status' => BookingState::STATUS_CANCELLED]);
                }
            });

            // Invalidate cache so slots are free again
            $this->clearBookingAvailabilityCache(
                $tenant->id,
                $service->id,
                Carbon::parse($selectedDate)->toDateString()
            );

            return [
                'error'      => 'Gagal memproses pembayaran. Error: ' . $e->getMessage(),
                'snap_error' => true,
            ];
        }

        // Invalidate cache immediately so slots are marked unavailable on public calendar
        $this->clearBookingAvailabilityCache(
            $tenant->id,
            $service->id,
            Carbon::parse($selectedDate)->toDateString()
        );

        return $result;
    }
}
