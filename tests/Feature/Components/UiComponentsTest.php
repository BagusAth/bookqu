<?php

declare(strict_types=1);

namespace Tests\Feature\Components;

use Tests\TestCase;

/**
 * The shared owner components render the design-system variants.
 */
class UiComponentsTest extends TestCase
{
    public function test_button_renders_each_variant_as_a_button_or_a_link(): void
    {
        foreach (['primary', 'secondary', 'ghost', 'danger', 'danger-solid', 'accent'] as $variant) {
            $this->blade('<x-ui.button :variant="$variant">Simpan</x-ui.button>', ['variant' => $variant])
                ->assertSee('class="bq-btn bq-btn-'.$variant.'"', false)
                ->assertSee('type="button"', false);
        }

        $this->blade('<x-ui.button href="/owner" variant="primary" size="sm" icon="plus">Tambah</x-ui.button>')
            ->assertSee('<a href="/owner"', false)
            ->assertSee('bq-btn bq-btn-primary bq-btn-sm', false)
            ->assertSee('<svg', false)
            ->assertSee('Tambah');
    }

    public function test_fields_mark_invalid_input_and_show_the_error(): void
    {
        $this->withViewErrors(['namabisnis' => 'Nama bisnis wajib diisi.']);

        $this->blade('<x-ui.field label="Nama bisnis" for="nama" name="namabisnis" hint="Tampil di halaman booking." required><x-ui.input id="nama" name="namabisnis" /></x-ui.field>')
            ->assertSee('Nama bisnis')
            ->assertSee('Tampil di halaman booking.')
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('Nama bisnis wajib diisi.')
            ->assertSee('bq-input w-full', false);

        $this->blade('<x-ui.select name="tipe"><option>Satu</option></x-ui.select><x-ui.textarea name="catatan">Isi</x-ui.textarea>')
            ->assertSee('name="tipe"', false)
            ->assertSee('name="catatan"', false)
            ->assertDontSee('aria-invalid', false);
    }

    public function test_panel_renders_title_description_actions_and_body(): void
    {
        $this->blade('<x-ui.panel title="Hari ini" description="Booking yang datang">
<x-slot:actions><a href="#">Lihat</a></x-slot:actions>
Isi
</x-ui.panel>')
            ->assertSee('bq-panel', false)->assertSee('Hari ini')->assertSee('Booking yang datang')->assertSee('Lihat')->assertSee('Isi');

    }

    public function test_stat_renders_a_hero_figure(): void
    {
        $this->blade('<x-ui.stat label="Saldo tersedia" value="Rp 18.500.000" context="Bisa dicairkan" hero :supporting="$supporting" />', ['supporting' => ['Booking' => 12]])
            ->assertSee('text-bq-figure', false)->assertSee('Rp 18.500.000')->assertSee('Bisa dicairkan')->assertSeeInOrder(['Booking', '12']);

    }

    public function test_badge_renders_each_tone(): void
    {
        foreach (['success', 'warning', 'danger', 'info', 'brand', 'accent', 'neutral'] as $tone) {
            $this->blade('<x-ui.badge :tone="$tone">Label</x-ui.badge>', ['tone' => $tone])->assertSee('data-tone="'.$tone.'"', false)->assertSee('bq-badge', false);
        }

    }

    public function test_list_row_renders_its_slots_in_order(): void
    {
        $this->blade('<x-ui.list-row href="/b/1">
<x-slot:leading>09.00</x-slot:leading>
Rani
<x-slot:trailing>Lunas</x-slot:trailing>
</x-ui.list-row>')
            ->assertSee('href="/b/1"', false)->assertSeeInOrder(['09.00', 'Rani', 'Lunas']);

    }

    public function test_avatar_and_skeleton_render(): void
    {
        $this->blade('<x-ui.avatar name="Rani Puspita" />')->assertSee('RP');
        $this->blade('<x-ui.skeleton :lines="2" />')->assertSee('animate-pulse', false)->assertSee('Memuat');
    }

    public function test_table_renders_group_selection_phone_subtitle_and_row_action(): void
    {
        $this->blade(<<<'BLADE'
<x-ui.table :columns="['Pelanggan', 'Status']" label="Booking" selectable actions>
    <x-slot:note><x-ui.table-note>Slot terbuka sampai 14 Des.</x-ui.table-note></x-slot:note>
    <x-slot:selection><x-ui.checkbox label="Pilih semua" hide-label /></x-slot:selection>
    <x-ui.table.row group>Senin, 16 Nov</x-ui.table.row>
    <x-ui.table.row two-lines>
        <x-slot:selection><x-ui.checkbox label="Pilih Rani" name="booking[]" value="12" hide-label /></x-slot:selection>
        <x-ui.table.cell phone="main" title="Rani Puspita">
            Rani Puspita
            <x-slot:subtitle title="Foto keluarga">Foto keluarga</x-slot:subtitle>
        </x-ui.table.cell>
        <x-ui.table.cell phone="end"><x-ui.status-dot tone="success">Terkonfirmasi</x-ui.status-dot></x-ui.table.cell>
        <x-slot:action>
            <x-ui.menu id="booking-actions">
                <button type="button" role="menuitem" tabindex="-1">Pindah jadwal</button>
            </x-ui.menu>
        </x-slot:action>
    </x-ui.table.row>
</x-ui.table>
BLADE)
            ->assertSee('role="table"', false)
            ->assertSee('bq-table-selectable bq-table-actions', false)
            ->assertSee('role="rowheader"', false)
            ->assertSee('name="booking[]" value="12"', false)
            ->assertSee('title="Rani Puspita"', false)
            ->assertSee('title="Foto keluarga"', false)
            ->assertSee('bg-bq-success', false)
            ->assertSee('aria-controls="booking-actions"', false)
            ->assertSeeInOrder(['Slot terbuka sampai 14 Des.', 'Pilih semua', 'Senin, 16 Nov', 'Pilih Rani', 'Foto keluarga', 'Terkonfirmasi', 'Pindah jadwal']);
    }

    public function test_form_controls_keep_native_values_and_selected_options(): void
    {
        $this->blade(<<<'BLADE'
<x-ui.form-section title="Kebijakan" description="Pilih pengaturan booking.">
    <x-ui.switch name="reminders" value="1" label="Pengingat" description="Kirim sebelum sesi" checked />
    <x-ui.radio-card name="payment" value="cash" label="Tunai" description="Diterima di tempat" checked />
    <x-ui.segmented label="Periode" name="period" :options="['day' => 'Hari', 'week' => 'Minggu']" selected="week" />
    <x-ui.segmented label="Tampilan" :options="['table' => ['label' => 'Tabel', 'href' => '/tabel']]" selected="table" />
</x-ui.form-section>
BLADE)
            ->assertSeeInOrder(['Kebijakan', 'Pilih pengaturan booking.', 'Pengingat', 'Kirim sebelum sesi', 'Tunai', 'Diterima di tempat'])
            ->assertSee('role="switch"', false)->assertSee('name="reminders" value="1" checked', false)
            ->assertSee('name="payment" value="cash" checked', false)
            ->assertSee('name="period" value="week" checked', false)
            ->assertSee('href="/tabel"', false)->assertSee('aria-current="page"', false);
    }

    public function test_layers_and_feedback_render_actions_and_accessible_states(): void
    {
        $this->blade(<<<'BLADE'
<x-ui.sheet id="hours" title="Jam tersedia">
    <x-ui.banner title="Slot hampir habis" tone="warning" />
    <x-ui.error-block title="Jam 13.00 baru saja terisi">
        <x-slot:action><button type="button">15.00</button></x-slot:action>
    </x-ui.error-block>
    <x-ui.empty title="Belum ada slot" description="Atur jam buka." />
    <x-slot:footer><button type="button">Simpan</button></x-slot:footer>
</x-ui.sheet>
BLADE)
            ->assertSee('aria-labelledby="hours-title"', false)
            ->assertSee('role="status"', false)
            ->assertSee('bg-bq-warning-bg', false)
            ->assertSee('role="alert"', false)
            ->assertSeeInOrder(['Jam tersedia', 'Slot hampir habis', 'Jam 13.00 baru saja terisi', '15.00', 'Belum ada slot', 'Atur jam buka.', 'Simpan']);
    }

    public function test_key_values_and_measurements_include_readable_units(): void
    {
        $this->blade(<<<'BLADE'
<x-ui.kv :items="['Layanan' => 'Foto keluarga']" />
<x-ui.duration :minutes="90" />
<x-ui.duration :minutes="30" />
<x-ui.mini-bar :value="6" :total="14" unit="jam" />
<x-ui.mini-bar :value="0" :total="0" />
BLADE)
            ->assertSee('<dl', false)
            ->assertSeeInOrder(['Layanan', 'Foto keluarga', '1,5 jam', '30 menit', '6 dari 14 jam', '6/14 jam'])
            ->assertSee('width: 43%', false)
            ->assertSee('width: 0%', false);
    }

    public function test_filter_bar_counts_active_filters_for_the_phone_toggle(): void
    {
        $this->blade('<x-ui.filter-bar :active="2">
<x-slot:search><input name="q"></x-slot:search>
<select name="s"></select>
<x-slot:actions><a href="#">Reset filter</a></x-slot:actions>
</x-ui.filter-bar>')
            ->assertSee('name="q"', false)
            ->assertSee('name="s"', false)
            ->assertSee('<span class="bq-count">2</span>', false)
            ->assertSee('Reset filter');
    }
}
