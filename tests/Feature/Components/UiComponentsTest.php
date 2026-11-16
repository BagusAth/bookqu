<?php

declare(strict_types=1);

namespace Tests\Feature\Components;

use Tests\TestCase;

/**
 * The shared owner components of BQ-161 render every variant with the design-system classes.
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
        $this->blade('<x-ui.stat label="Saldo tersedia" value="Rp 18.500.000" context="Bisa dicairkan" hero />')
            ->assertSee('text-3xl', false)->assertSee('Rp 18.500.000')->assertSee('Bisa dicairkan');

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
        $this->blade('<x-ui.skeleton :lines="2" />')->assertSee('animate-pulse', false);
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
