<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Payment\PaymentState;
use PHPUnit\Framework\TestCase;

class PaymentStateTest extends TestCase
{
    public function test_can_transition_from_pending_to_sukses_and_gagal(): void
    {
        $this->assertTrue(PaymentState::canTransition(PaymentState::STATUS_PENDING, PaymentState::STATUS_SUKSES));
        $this->assertTrue(PaymentState::canTransition(PaymentState::STATUS_PENDING, PaymentState::STATUS_GAGAL));
        $this->assertTrue(PaymentState::canTransition(PaymentState::STATUS_PENDING, PaymentState::STATUS_PENDING));
    }

    public function test_terminal_statuses_cannot_transition(): void
    {
        $this->assertTrue(PaymentState::isTerminal(PaymentState::STATUS_SUKSES));
        $this->assertTrue(PaymentState::isTerminal(PaymentState::STATUS_GAGAL));
        $this->assertFalse(PaymentState::isTerminal(PaymentState::STATUS_PENDING));

        $this->assertFalse(PaymentState::canTransition(PaymentState::STATUS_SUKSES, PaymentState::STATUS_GAGAL));
        $this->assertFalse(PaymentState::canTransition(PaymentState::STATUS_GAGAL, PaymentState::STATUS_SUKSES));
    }

    public function test_is_valid_status(): void
    {
        $this->assertTrue(PaymentState::isValidStatus('pending'));
        $this->assertTrue(PaymentState::isValidStatus('sukses'));
        $this->assertTrue(PaymentState::isValidStatus('gagal'));
        $this->assertFalse(PaymentState::isValidStatus('completed'));
        $this->assertFalse(PaymentState::isValidStatus('unknown_status'));
    }

    public function test_map_transaction_status(): void
    {
        // Settlement -> sukses
        $this->assertEquals(PaymentState::STATUS_SUKSES, PaymentState::mapTransactionStatus('settlement'));

        // Capture with accept -> sukses
        $this->assertEquals(PaymentState::STATUS_SUKSES, PaymentState::mapTransactionStatus('capture', 'accept'));

        // Capture with challenge -> pending
        $this->assertEquals(PaymentState::STATUS_PENDING, PaymentState::mapTransactionStatus('capture', 'challenge'));

        // Pending -> pending
        $this->assertEquals(PaymentState::STATUS_PENDING, PaymentState::mapTransactionStatus('pending'));

        // Deny, expire, cancel -> gagal
        $this->assertEquals(PaymentState::STATUS_GAGAL, PaymentState::mapTransactionStatus('deny'));
        $this->assertEquals(PaymentState::STATUS_GAGAL, PaymentState::mapTransactionStatus('expire'));
        $this->assertEquals(PaymentState::STATUS_GAGAL, PaymentState::mapTransactionStatus('cancel'));

        // Unknown status -> unknown
        $this->assertEquals('unknown', PaymentState::mapTransactionStatus('refund_requested'));
    }

    public function test_format_label(): void
    {
        $this->assertEquals('Berhasil', PaymentState::formatLabel(PaymentState::STATUS_SUKSES));
        $this->assertEquals('Menunggu Pembayaran', PaymentState::formatLabel(PaymentState::STATUS_PENDING));
        $this->assertEquals('Gagal / Dibatalkan', PaymentState::formatLabel(PaymentState::STATUS_GAGAL));
    }
}
