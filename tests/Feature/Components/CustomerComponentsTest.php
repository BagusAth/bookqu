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

    public function test_design_a_components_render_prepared_data_with_customer_tokens(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-customer.hero name="Studio Cahaya" description="Studio foto keluarga" address="Jl. Merdeka 1" maps-url="https://maps.test" wa-url="https://wa.test" :badges="['Bayar online']" />
            <x-customer.step-header title="Pilih jadwal" :step="2" back-url="/studio" service="Sesi foto" change-url="/studio" />
            <x-customer.service-card href="/a" name="Sesi foto" price="Rp150.000" duration="60 menit" category="Foto" />
            <x-customer.service-row href="/b" name="Sesi video" price="Rp300.000" duration="120 menit" />
            <x-customer.category-chips :categories="[['key' => 'all', 'label' => 'Semua layanan', 'short' => 'Semua', 'count' => 2]]" active="all" />
            <x-customer.summary-panel title="Sesi foto" :rows="['Tanggal' => 'Sen, 23 Nov 2026']" total="Rp150.000" />
            <x-customer.ticket month="Nov" day="23" weekday="Senin" service="Sesi foto" time="14.00 – 15.00 WIB" code="BQ-7F3K2" />
            <x-customer.status-note tone="warning" title="Batas ubah jadwal lewat" />
            <x-customer.empty-state title="Tidak ada layanan" />
            <x-customer.bottom-bar label="1 sesi" total="Rp150.000"><button class="sf-btn">Lanjut</button></x-customer.bottom-bar>
            BLADE);

        foreach ([
            'Reservasi di Studio Cahaya', 'Studio foto keluarga', 'Petunjuk arah', 'Bantuan via WhatsApp', 'Bayar online',
            'Langkah 2 dari 3', 'Pilih jadwal', 'Ganti', 'Favorit', 'Rp150.000', 'Rp300.000', 'aria-pressed="true"',
            'Pilihan Anda', 'Kode reservasi', 'BQ-7F3K2', 'Salin', 'data-tone="warning"', 'Tidak ada layanan', 'sf-bottom-bar',
        ] as $text) {
            $this->assertStringContainsString($text, $html);
        }
        $this->assertDoesNotMatchRegularExpression('/#[0-9A-Fa-f]{6}\b/', $html, 'Customer components use tokens, not hex colours.');
    }

    public function test_sheet_is_a_labelled_modal_dialog(): void
    {
        $html = Blade::render('<x-customer.sheet id="kalender" title="Pilih tanggal">Isi</x-customer.sheet>');

        foreach (['role="dialog"', 'aria-modal="true"', 'aria-labelledby="kalender-title"', 'Pilih tanggal', 'aria-label="Tutup"', 'ownerDialog'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
    }
}
