<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Domain\Payment\PaymentRules;
use App\Domain\Payment\PaymentState;
use App\Infrastructure\Payments\Midtrans\MidtransPaymentGateway;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Tenant;

class CreateBookingPayment
{
    public function __construct(
        protected MidtransPaymentGateway $gateway
    ) {
    }

    /**
     * Create payment record for a free booking.
     *
     * @param Tenant $tenant
     * @param array<string, mixed> $customerData
     * @param string|null $fullCatatan
     * @return Payment
     */
    public function createFreePayment(Tenant $tenant, array $customerData, ?string $fullCatatan = null): Payment
    {
        $orderId = PaymentRules::generateOrderId('FREE', (int) $tenant->id);

        return Payment::create([
            'idtenant'       => $tenant->id,
            'tipe'           => PaymentState::TIPE_BOOKING,
            'jumlah'         => 0,
            'status'         => PaymentState::STATUS_SUKSES,
            'metode'         => PaymentState::METODE_GRATIS,
            'order_id'       => $orderId,
            'manage_token'   => Booking::generateSecureToken(),
            'nama_pembayar'  => $customerData['namapelanggan'],
            'email_pembayar' => $customerData['email'],
            'hp_pembayar'    => $customerData['nomorhp'],
            'catatan'        => $fullCatatan ?: null,
        ]);
    }

    /**
     * Create initial pending payment record for a paid booking.
     *
     * @param Tenant $tenant
     * @param float $hargaAkhir
     * @param array<string, mixed> $customerData
     * @param string|null $fullCatatan
     * @return Payment
     */
    public function createPendingPayment(
        Tenant $tenant,
        float $hargaAkhir,
        array $customerData,
        ?string $fullCatatan = null
    ): Payment {
        $orderId = PaymentRules::generateOrderId('BKG', (int) $tenant->id);

        return Payment::create([
            'idtenant'       => $tenant->id,
            'tipe'           => PaymentState::TIPE_BOOKING,
            'jumlah'         => $hargaAkhir,
            'status'         => PaymentState::STATUS_PENDING,
            'metode'         => PaymentState::METODE_MIDTRANS,
            'order_id'       => $orderId,
            'manage_token'   => Booking::generateSecureToken(),
            'expired_at'     => now()->addMinutes(15),
            'nama_pembayar'  => $customerData['namapelanggan'],
            'email_pembayar' => $customerData['email'],
            'hp_pembayar'    => $customerData['nomorhp'],
            'catatan'        => $fullCatatan ?: null,
        ]);
    }

    /**
     * Request and associate Snap token for a paid booking payment.
     *
     * @param Payment $payment
     * @param Service $service
     * @param array<string, mixed> $customerData
     * @param int $slotCount
     * @return string
     * @throws \Exception
     */
    public function generateSnapToken(
        Payment $payment,
        Service $service,
        array $customerData,
        int $slotCount = 1
    ): string {
        $itemName = 'Booking: ' . $service->namalayanan;
        if ($slotCount > 1) {
            $itemName .= ' (' . $slotCount . ' slot)';
        }

        $params = [
            'transaction_details' => [
                'order_id'     => $payment->order_id,
                'gross_amount' => (int) $payment->jumlah,
            ],
            'customer_details' => [
                'first_name' => $customerData['namapelanggan'],
                'email'      => $customerData['email'],
                'phone'      => $customerData['nomorhp'],
            ],
            'item_details' => [
                [
                    'id'       => 'SRV-' . $service->id,
                    'price'    => (int) $payment->jumlah,
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

        $snapToken = $this->gateway->createSnapToken($payment, $params);
        $payment->update(['snap_token' => $snapToken]);

        return $snapToken;
    }
}
