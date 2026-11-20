<?php

namespace Tests\Feature\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

final class CustomerComponentsTest extends TestCase
{
    public function test_summary_and_heading_render_prepared_customer_data(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-customer.page-header title="Reservasi Anda" description="Periksa jadwal" />
            <x-customer.summary :items="['Layanan' => 'Sesi foto']" total="Rp150.000" />
            <x-customer.status label="Lunas" tone="success" />
            BLADE);

        foreach (['Reservasi Anda', 'Periksa jadwal', 'Sesi foto', 'Rp150.000', 'Lunas'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
    }

    public function test_date_time_and_quantity_controls_have_accessible_states(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-customer.stepper label="Cetak foto" model="quantity" id="prints" :value="2" />
            <x-customer.date-strip :dates="[['date' => '2026-11-24', 'label' => 'Selasa, 24 Nov', 'is_available' => false]]" current-date="2026-11-24" />
            <x-customer.time-grid />
            <x-customer.help-link href="https://example.test/help" />
            BLADE);

        foreach (['Kurangi Cetak foto', 'Tambah Cetak foto', 'value="2"', 'aria-current="date"', 'Jadwal saat ini', 'aria-pressed', 'Bantuan lewat WhatsApp'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
        $this->assertSame('', trim(Blade::render('<x-customer.help-link />')));
    }
}
