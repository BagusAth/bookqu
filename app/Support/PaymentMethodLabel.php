<?php

declare(strict_types=1);

namespace App\Support;

/**
 * The owner-facing name of a stored payment method (BQ-161 audit BK-04): views never show raw values such
 * as `kartu_kredit`. Unknown values fall back to a readable form of the stored value.
 */
final class PaymentMethodLabel
{
    private const LABELS = [
        'cash' => 'Tunai',
        'tunai' => 'Tunai',
        'transfer' => 'Transfer bank',
        'transfer_bank' => 'Transfer bank',
        'bank_transfer' => 'Transfer bank',
        'qris' => 'QRIS',
        'manual' => 'Lainnya (manual)',
        'kartu_kredit' => 'Kartu kredit',
        'credit_card' => 'Kartu kredit',
        'ewallet' => 'E-wallet',
        'e_wallet' => 'E-wallet',
        'gopay' => 'GoPay',
        'shopeepay' => 'ShopeePay',
        'ovo' => 'OVO',
        'dana' => 'DANA',
        'virtual_account' => 'Virtual account',
        'va' => 'Virtual account',
        'singapay' => 'Pembayaran online',
        'midtrans' => 'Pembayaran online',
        'gratis' => 'Gratis',
    ];

    public static function for(?string $method): ?string
    {
        $key = strtolower(trim((string) $method));
        if ($key === '') {
            return null;
        }

        return self::LABELS[$key] ?? ucfirst(str_replace(['_', '-'], ' ', $key));
    }
}
