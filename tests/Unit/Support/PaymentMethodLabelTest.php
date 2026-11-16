<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\PaymentMethodLabel;
use PHPUnit\Framework\TestCase;

class PaymentMethodLabelTest extends TestCase
{
    public function test_stored_methods_get_owner_facing_names(): void
    {
        $this->assertSame('Kartu kredit', PaymentMethodLabel::for('kartu_kredit'));
        $this->assertSame('Tunai', PaymentMethodLabel::for('cash'));
        $this->assertSame('QRIS', PaymentMethodLabel::for('QRIS'));
        $this->assertSame('Pembayaran online', PaymentMethodLabel::for('singapay'));
    }

    public function test_unknown_methods_are_readable_and_empty_ones_have_no_label(): void
    {
        $this->assertSame('Bayar nanti', PaymentMethodLabel::for('bayar_nanti'));
        $this->assertNull(PaymentMethodLabel::for(null));
        $this->assertNull(PaymentMethodLabel::for('  '));
    }
}
