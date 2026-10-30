<?php

namespace App\Actions\Booking;

use App\Actions\CustomerAccess\IssueCustomerAccess;
use App\Actions\CustomerAccess\RequestManagementDelivery;
use App\Actions\Payment\CreateBookingPayment;
use App\Actions\Payment\PaymentLifecycle;
use App\Actions\Schedule\ClaimSlots;
use App\Actions\Schedule\SlotClaimRefused;
use App\Domain\Booking\BookingRules;
use App\Domain\Booking\BookingState;
use App\Domain\CustomerAccess\AccessProfile;
use App\Domain\Payment\PaymentTransition;
use App\Domain\Subscription\EntitlementRules;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Notifications\NewBookingOwnerNotification;
use App\Traits\ClearsBookingCache;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateBooking
{
    use ClearsBookingCache;

    protected CreateBookingPayment $bookingPaymentAction;

    protected PaymentLifecycle $paymentLifecycle;

    protected ClaimSlots $claimSlots;

    public function __construct(
        ?CreateBookingPayment $bookingPaymentAction = null,
        ?PaymentLifecycle $paymentLifecycle = null,
        ?ClaimSlots $claimSlots = null
    ) {
        $this->bookingPaymentAction = $bookingPaymentAction ?? app(CreateBookingPayment::class);
        $this->paymentLifecycle = $paymentLifecycle ?? app(PaymentLifecycle::class);
        $this->claimSlots = $claimSlots ?? app(ClaimSlots::class);
    }

    /**
     * Execute the customer booking checkout workflow.
     *
     * @param  array{namapelanggan: string, nomorhp: string, email: string, catatan: ?string}  $customerData
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
        try {
            $result = DB::transaction(function () use ($tenant, $service, $selectedDate, $scheduleIds, $customerData, $slotCount) {
                $wib = 'Asia/Jakarta';

                // Tenant-first synchronization before any child row (ADR-0005 decision 4)
                ClaimSlots::lockTenant((int) $tenant->id);

                // 1. Atomic monthly booking quota check inside transaction: lock stable synchronization row (Subscription or Tenant)
                $subscription = Subscription::with('plan')
                    ->where('idtenant', $tenant->id)
                    ->latest()
                    ->lockForUpdate()
                    ->first();

                if (! $subscription) {
                    DB::table('tenants')->where('id', $tenant->id)->lockForUpdate()->first();
                }

                if (! EntitlementRules::isUnlimitedBooking($subscription)) {
                    $dateCarbon = Carbon::parse($selectedDate, $wib);
                    $totalMonthlyBookings = DB::table('bookings')
                        ->where('idtenant', $tenant->id)
                        ->whereYear('tanggalbooking', $dateCarbon->year)
                        ->whereMonth('tanggalbooking', $dateCarbon->month)
                        ->whereIn('status', [BookingState::STATUS_PENDING, BookingState::STATUS_PAID, BookingState::STATUS_COMPLETED])
                        ->count();

                    $bookingQuota = EntitlementRules::canCreateBooking($subscription, $totalMonthlyBookings, $slotCount);
                    if (! $bookingQuota['allowed'] && $bookingQuota['message']) {
                        return [
                            'quota_error' => true,
                            'error' => $bookingQuota['message'],
                        ];
                    }
                }

                // 2. Claim the selected schedules: lock, verify ownership, reclaim stale pending
                // bookings and re-check them with the rule listings use (ADR-0005)
                $orderedSchedules = $this->claimSlots
                    ->execute($tenant, (int) $service->id, $scheduleIds, $selectedDate)
                    ->all();

                // 3. Contiguous validation: Sort schedules by jam_mulai ascending
                usort($orderedSchedules, fn ($a, $b) => strcmp($a->jam_mulai, $b->jam_mulai));
                $durationMinutes = (int) $service->durasi;

                if (! BookingRules::validateContiguousSlots($orderedSchedules, $durationMinutes, $selectedDate, $wib)) {
                    return ['error' => 'Slot harus berurutan tanpa jeda.'];
                }

                // 4. Calculate slot prices via domain rule
                $pricing = BookingRules::calculateSlotPrices($orderedSchedules, $service);
                $hargaAkhir = $pricing['total'];

                $fullCatatan = trim($customerData['catatan'] ?? '');
                if ($slotCount > 1) {
                    $timeList = implode(', ', array_map(fn ($s) => substr($s->jam_mulai, 0, 5), $orderedSchedules));
                    $fullCatatan .= ($fullCatatan ? ' | ' : '')."Multi-slot ({$slotCount}x): {$timeList}";
                }

                // 5. Free booking path ($0)
                if ($hargaAkhir <= 0) {
                    $payment = $this->bookingPaymentAction->createFreePayment($tenant, $customerData, $fullCatatan);

                    $createdBookings = [];
                    foreach ($orderedSchedules as $schedule) {
                        $slotTime = Carbon::parse($selectedDate.' '.$schedule->jam_mulai, $wib)->format('H:i');
                        $bk = Booking::create([
                            'idtenant' => $tenant->id,
                            'idlayanan' => $service->id,
                            'idschedule' => $schedule->id,
                            'namapelanggan' => $customerData['namapelanggan'],
                            'nomorhp' => $customerData['nomorhp'],
                            'email' => $customerData['email'],
                            'tanggalbooking' => $selectedDate,
                            'jam' => $slotTime.':00',
                            'status' => BookingState::STATUS_PAID,
                            'idpayment' => $payment->id,
                            'booking_code' => Booking::generateBookingCode(),

                            'catatan' => $fullCatatan ?: null,
                        ]);
                        $createdBookings[] = $bk;
                    }

                    if (! empty($createdBookings)) {
                        $payment->update(['idbooking' => $createdBookings[0]->id]);
                    }

                    app(RequestManagementDelivery::class)->execute($payment, 'confirmed');

                    return [
                        'access' => app(IssueCustomerAccess::class)->execute($payment, AccessProfile::Checkout),
                        'free' => true,
                        'payment' => $payment,
                        'bookings' => $createdBookings,
                        'schedules' => $orderedSchedules,
                        'hargaAkhir' => 0,
                    ];
                }

                // 6. Paid booking path: create single payment and N bookings with status 'pending'
                $payment = $this->bookingPaymentAction->createPendingPayment($tenant, $hargaAkhir, $customerData, $fullCatatan);
                $orderId = $payment->order_id;

                $createdBookings = [];
                foreach ($orderedSchedules as $schedule) {
                    $slotTime = Carbon::parse($selectedDate.' '.$schedule->jam_mulai, $wib)->format('H:i');
                    $newBooking = Booking::create([
                        'idtenant' => $tenant->id,
                        'idlayanan' => $service->id,
                        'idschedule' => $schedule->id,
                        'namapelanggan' => $customerData['namapelanggan'],
                        'nomorhp' => $customerData['nomorhp'],
                        'email' => $customerData['email'],
                        'tanggalbooking' => $selectedDate,
                        'jam' => $slotTime.':00',
                        'status' => BookingState::STATUS_PENDING,
                        'idpayment' => $payment->id,
                        'booking_code' => Booking::generateBookingCode(),

                        'catatan' => $fullCatatan ?: null,
                    ]);
                    $createdBookings[] = $newBooking;
                }

                if (! empty($createdBookings)) {
                    $payment->update(['idbooking' => $createdBookings[0]->id]);
                }

                return [
                    'access' => app(IssueCustomerAccess::class)->execute($payment, AccessProfile::Checkout),
                    'free' => false,
                    'bookings' => $createdBookings,
                    'payment' => $payment,
                    'schedules' => $orderedSchedules,
                    'orderId' => $orderId,
                    'hargaAkhir' => $hargaAkhir,
                ];
            });
        } catch (SlotClaimRefused $refused) {
            return ['error' => $refused->getMessage()];
        }

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
                        Log::error('Gagal kirim notif free booking ke owner: '.$e->getMessage());
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

        // Paid booking post-processing: Initialize payment via active gateway driver
        $payment = $result['payment'];
        $bookings = $result['bookings'];
        $hargaAkhir = $result['hargaAkhir'];
        $orderId = $result['orderId'];

        try {
            $paymentResult = $this->bookingPaymentAction->initializePayment(
                $payment,
                $service,
                $customerData,
                count($bookings)
            );
            $result['payment_url'] = $paymentResult['payment_url'] ?? $payment->payment_url;
            $result['snapToken'] = $paymentResult['snap_token'] ?? $payment->snap_token;
        } catch (Exception $e) {
            Log::error('Payment Initialization Error (Booking): '.$e->getMessage());
            // Payment initialization failed — the payment fails and its bookings are cancelled
            $this->paymentLifecycle->transition($payment, PaymentTransition::InitiationFailed);

            // Invalidate cache so slots are free again
            $this->clearBookingAvailabilityCache(
                $tenant->id,
                $service->id,
                Carbon::parse($selectedDate)->toDateString()
            );

            return [
                'error' => 'Gagal memproses pembayaran. Error: '.$e->getMessage(),
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
