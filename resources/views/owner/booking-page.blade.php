@extends('layouts.owner-layout')

@section('title', 'Halaman Booking')

{{--
    Halaman Booking (BQ-142, owner-portal.md §8.9): Bagikan, Tampilan and Galeri, for every plan (D-009, D-010), plus Halaman depan and Domain sendiri for Pro (BQ-154), which
    show the upgrade prompt below Pro (§3 rule 6). Data from App\Actions\Tenant\GetBookingPageData.
--}}

@php
    $card = 'bq-panel';
    $buttonSecondary = 'bq-btn bq-btn-secondary bq-btn-sm';
    $buttonPrimary = 'bq-btn bq-btn-primary';
    $field = 'mt-1 block w-full bq-input';
    $label = 'block text-sm font-medium text-bq-text';
    $destroyUrl = route('owner.booking-page.media.destroy', ['asset' => '__ID__']);
    $sections = collect(\App\Actions\Tenant\GetBookingPageData::SECTIONS);
@endphp

@section('content')
<div class="mx-auto max-w-7xl space-y-6" x-data="{
    dirtyAppearance: false,
    pendingSection: null,
    confirmSection(event) {
        const link = event.target.closest('a[href]');
        if (!link || !this.dirtyAppearance || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        this.pendingSection = link.href;
        $dispatch('open-confirm', { title: 'Buang perubahan tampilan?', message: 'Perubahan tampilan belum disimpan.', formId: 'leave-booking-page-section', confirmText: 'Buang perubahan' });
    }
}">
    <x-owner.page-header title="Halaman Booking" purpose="Bagaimana pelanggan menemukan Anda, dan apa yang mereka lihat.">
        @if ($activeSection === 'galeri')
            <x-slot:action>
                <x-ui.button href="#booking-gallery-upload" variant="primary" icon="plus">Unggah gambar</x-ui.button>
            </x-slot:action>
        @endif
    </x-owner.page-header>

    <div @click.capture="confirmSection($event)">
        <x-owner.tabs :tabs="$sections->all()" :counts="['galeri' => count($gallery)]" param="section" :active="$activeSection" label="Bagian Halaman Booking" />
    </div>
    <form id="leave-booking-page-section" @submit.prevent="if (pendingSection) window.location.assign(pendingSection)" hidden></form>

    {{-- ── Link & QR (FR-PUBLIC-006, AC-HBK-02) ── --}}
    @if ($activeSection === 'link')
        <div class="grid gap-4 md:grid-cols-2">
            <x-ui.panel title="Link booking">
                <div class="grid gap-3" x-data="ownerBookingLink(@js(['url' => $link, 'share_text' => 'Booking '.($tenant->namabisnis ?: 'kami').' online: '.$link]), @js(route('owner.dashboard.link-shared')))">
                    <div class="flex flex-wrap items-center justify-between gap-2 rounded-bq-control border border-bq-border bg-bq-background px-3 py-2">
                        <p class="min-w-0 flex-1 break-all text-bq-body">{{ $link }}</p>
                        <x-ui.button icon="copy" size="sm" x-on:click="copy()" x-text="copied ? 'Tersalin' : 'Salin link'">Salin link</x-ui.button>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <x-ui.button :href="$link" target="_blank" rel="noopener" size="sm" icon="external">Buka</x-ui.button>
                        <x-ui.button :href="$whatsappShareUrl" target="_blank" rel="noopener" size="sm" icon="whatsapp" x-on:click="mark()">Bagikan ke WhatsApp</x-ui.button>
                    </div>
                </div>
            </x-ui.panel>

            <x-ui.panel title="Kode QR">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="w-28 shrink-0 [&>svg]:h-auto [&>svg]:w-full" role="img" aria-label="Kode QR untuk {{ $link }}">{!! $qrSvg !!}</div>
                    <div class="grid justify-items-start gap-2">
                        <x-ui.button :href="route('owner.booking-page.qr')" size="sm" icon="download">Unduh QR</x-ui.button>
                        <x-ui.button :href="route('owner.booking-page.poster')" target="_blank" rel="noopener" size="sm" icon="download">Cetak poster</x-ui.button>
                    </div>
                </div>
            </x-ui.panel>
        </div>
        <x-ui.panel title="Teks siap pakai">
            <div class="grid gap-4 md:grid-cols-2">
                @foreach ([['Bio Instagram', 'instagram', 'Salin teks bio Instagram'], ['Pesan WhatsApp', 'whatsapp', 'Salin pesan WhatsApp']] as [$title, $key, $action])
                    <section class="grid min-w-0 justify-items-start gap-2" x-data="ownerCopyText(@js($materials[$key]))">
                        <h3 class="text-bq-body font-semibold text-bq-text">{{ $title }}</h3>
                        <p class="w-full whitespace-pre-line break-words rounded-bq-control border border-bq-border bg-bq-background px-3 py-2 text-bq-body text-bq-text">{{ $materials[$key] }}</p>
                        <button type="button" class="{{ $buttonSecondary }}" x-on:click="copy()" x-text="copied ? 'Tersalin' : @js($action)">{{ $action }}</button>
                    </section>
                @endforeach
            </div>
        </x-ui.panel>
    @endif

    {{-- ── Tampilan (FR-PUBLIC-003, AC-HBK-01) ── --}}
    @if ($activeSection === 'tampilan')
        <div
            class="grid gap-6 lg:grid-cols-[1fr_20rem]"
            x-data="{
                look: @js(['theme_color' => old('theme_color', $appearance['theme_color']), 'button_style' => old('button_style', $appearance['button_style']), 'card_style' => old('card_style', $appearance['card_style']), 'font_family' => old('font_family', $appearance['font_family']), 'logo_asset_id' => (string) old('logo_asset_id', $appearance['logo_asset_id'] ?? ''), 'cover_asset_id' => (string) old('cover_asset_id', $appearance['cover_asset_id'] ?? '')]),
                initialLook: null,
                init() { this.initialLook = JSON.stringify(this.look); },
                images: @js($pickerImages),
                image(id) { const found = this.images.find(i => String(i.id) === String(id)); return found ? found.url : null; },
                templates: @js(\App\Support\StorefrontBranding::TEMPLATES),
                shapes: @js(\App\Support\StorefrontBranding::SHAPES),
                surfaces: @js(\App\Support\StorefrontBranding::SURFACES),
                get template() {
                    const match = Object.entries(this.templates).find(([, t]) => t.button_style === this.look.button_style && t.card_style === this.look.card_style && t.font_family === this.look.font_family);
                    return match ? match[0] : 'custom';
                },
                applyTemplate(key) {
                    const t = this.templates[key];
                    this.look.button_style = t.button_style;
                    this.look.card_style = t.card_style;
                    this.look.font_family = t.font_family;
                },
                get previewStyle() {
                    const hex = /^#[0-9a-f]{6}$/i.test(this.look.theme_color) ? this.look.theme_color : '#4F46E5';
                    const channel = i => { const c = parseInt(hex.slice(i, i + 2), 16) / 255; return c <= 0.04045 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4); };
                    const light = 0.2126 * channel(1) + 0.7152 * channel(3) + 0.0722 * channel(5) > 0.18;
                    const shape = this.shapes[this.look.button_style] || this.shapes.rounded;
                    const surface = this.surfaces[this.look.card_style] || this.surfaces.elevated;
                    const font = String(this.look.font_family).replace(/[^A-Za-z ]/g, '');
                    return [
                        '--sf-primary:' + hex,
                        '--sf-primary-hover:color-mix(in srgb,' + hex + ' 86%,' + (light ? '#FFFFFF' : '#000000') + ')',
                        '--sf-on-primary:' + (light ? '#17151F' : '#FFFFFF'),
                        '--sf-on-primary-soft:' + (light ? 'rgba(23,21,31,0.80)' : 'rgba(255,255,255,0.86)'),
                        '--sf-on-primary-faint:' + (light ? 'rgba(23,21,31,0.10)' : 'rgba(255,255,255,0.14)'),
                        '--sf-on-primary-line:' + (light ? 'rgba(23,21,31,0.18)' : 'rgba(255,255,255,0.24)'),
                        '--sf-primary-text:' + (light ? 'color-mix(in srgb,' + hex + ' 42%,#000000)' : hex),
                        '--sf-tint:color-mix(in srgb,' + hex + ' 9%,#FFFFFF)',
                        '--sf-tint-strong:color-mix(in srgb,' + hex + ' 16%,#FFFFFF)',
                        '--sf-radius-control:' + shape.control, '--sf-radius-tile:' + shape.tile, '--sf-radius-area:' + shape.area,
                        '--sf-radius-chip:' + shape.chip, '--sf-radius-card:' + shape.card, '--sf-radius-card-small:' + shape.cardSmall,
                        '--sf-shadow-card:' + surface.card, '--sf-shadow-raised:' + surface.raised,
                        'font-family:' + JSON.stringify(font) + ',system-ui,sans-serif',
                    ].join(';');
                },
            }"
            x-effect="dirtyAppearance = initialLook !== null && JSON.stringify(look) !== initialLook"
        >
            <form method="POST" action="{{ route('owner.booking-page.appearance') }}" class="{{ $card }} space-y-5 p-4">
                @csrf
                @php($currentTemplate = \App\Support\StorefrontBranding::templateKey(old('button_style', $appearance['button_style']), old('card_style', $appearance['card_style']), old('font_family', $appearance['font_family'])))
                {{-- Ready-made looks (D-028): a template fills button style, card style and font; the owner still saves. --}}
                <fieldset>
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <legend class="{{ $label }}">Template</legend>
                        <p class="text-sm text-bq-text-muted" aria-live="polite">Dipakai: <span class="font-semibold text-bq-text" x-text="template === 'custom' ? 'Kustom' : templates[template].label">{{ $currentTemplate === 'custom' ? 'Kustom' : \App\Support\StorefrontBranding::TEMPLATES[$currentTemplate]['label'] }}</span></p>
                    </div>
                    <div class="mt-2 grid gap-2 sm:grid-cols-3">
                        @foreach (\App\Support\StorefrontBranding::TEMPLATES as $key => $template)
                            <button type="button" data-template="{{ $key }}" aria-pressed="{{ $currentTemplate === $key ? 'true' : 'false' }}"
                                x-on:click="applyTemplate(@js($key))" x-bind:aria-pressed="(template === @js($key)).toString()"
                                class="rounded-bq-control border border-bq-border bg-bq-surface p-3 text-left transition-colors hover:border-bq-border-strong aria-pressed:border-bq-primary aria-pressed:bg-bq-primary-light">
                                <span class="flex items-center gap-2" aria-hidden="true">
                                    <span class="h-9 w-16 bg-bq-primary" style="border-radius: {{ \App\Support\StorefrontBranding::SHAPES[$template['button_style']]['control'] }}"></span>
                                    <span class="h-9 w-10 border border-bq-border bg-bq-surface" style="border-radius: {{ \App\Support\StorefrontBranding::SHAPES[$template['button_style']]['cardSmall'] }}"></span>
                                </span>
                                <span class="mt-2 block text-sm font-semibold text-bq-text">{{ $template['label'] }}</span>
                                <span class="block text-xs text-bq-text-muted">{{ \App\Support\StorefrontBranding::BUTTON_STYLES[$template['button_style']]['label'] }} · {{ \App\Support\StorefrontBranding::CARD_STYLES[$template['card_style']]['label'] }} · {{ $template['font_family'] }}</span>
                            </button>
                        @endforeach
                    </div>
                    <p class="mt-2 text-sm text-bq-text-muted">Warna tema tetap milik Anda. Setelah memilih template, setiap pengaturan masih bisa diubah.</p>
                </fieldset>
                @if (count($pickerImages) === 0)
                    <x-owner.empty-state title="Halaman Anda masih memakai tampilan bawaan BookQu" description="Tambahkan logo dan sampul ke galeri media, lalu pilih di sini." action-label="Buka galeri media" :action-href="route('owner.booking-page', ['section' => 'galeri'])" />
                @endif
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <p class="{{ $label }}">Logo</p>
                        <x-owner.gallery-picker name="logo_asset_id" :images="$pickerImages" model="look.logo_asset_id" label="Pilih logo" />
                        @error('logo_asset_id')<p class="mt-1 text-xs text-bq-danger">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <p class="{{ $label }}">Sampul</p>
                        <x-owner.gallery-picker name="cover_asset_id" :images="$pickerImages" model="look.cover_asset_id" label="Pilih sampul" />
                        @error('cover_asset_id')<p class="mt-1 text-xs text-bq-danger">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label for="theme-color" class="{{ $label }}">Warna tema</label>
                    <div class="mt-1 flex flex-wrap items-center gap-2">
                        @foreach ($branding['colors'] as $color)
                            <button type="button" class="h-8 w-8 rounded-full ring-offset-2 focus:outline-none focus:ring-2 focus:ring-bq-primary" style="background-color: {{ $color }}" aria-label="Warna {{ $color }}" x-on:click="look.theme_color = @js($color)" x-bind:class="look.theme_color.toUpperCase() === @js($color) ? 'ring-2 ring-bq-text' : ''"></button>
                        @endforeach
                        <input id="theme-color" type="text" name="theme_color" x-model="look.theme_color" maxlength="7" class="bq-input w-28 font-mono uppercase">
                    </div>
                    @error('theme_color')<p class="mt-1 text-xs text-bq-danger">{{ $message }}</p>@enderror
                </div>

                <fieldset>
                    <legend class="{{ $label }}">Gaya tombol</legend>
                    <div class="mt-1 flex flex-wrap gap-2">
                        @foreach ($branding['buttons'] as $key => $style)
                            <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-bq-border px-3 py-1.5 text-sm has-[:checked]:border-bq-primary has-[:checked]:bg-bq-primary-light">
                                <input type="radio" name="button_style" value="{{ $key }}" x-model="look.button_style" class="text-bq-primary"> {{ $style['label'] }}
                            </label>
                        @endforeach
                    </div>
                    @error('button_style')<p class="mt-1 text-xs text-bq-danger">{{ $message }}</p>@enderror
                </fieldset>

                <fieldset>
                    <legend class="{{ $label }}">Gaya kartu</legend>
                    <div class="mt-1 flex flex-wrap gap-2">
                        @foreach ($branding['cards'] as $key => $style)
                            <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-bq-border px-3 py-1.5 text-sm has-[:checked]:border-bq-primary has-[:checked]:bg-bq-primary-light">
                                <input type="radio" name="card_style" value="{{ $key }}" x-model="look.card_style" class="text-bq-primary"> {{ $style['label'] }}
                            </label>
                        @endforeach
                    </div>
                    @error('card_style')<p class="mt-1 text-xs text-bq-danger">{{ $message }}</p>@enderror
                </fieldset>

                <div>
                    <label for="font-family" class="{{ $label }}">Huruf</label>
                    <select id="font-family" name="font_family" x-model="look.font_family" class="{{ $field }} sm:w-64">
                        @foreach ($branding['fonts'] as $font)
                            <option value="{{ $font }}">{{ $font }}</option>
                        @endforeach
                    </select>
                    @error('font_family')<p class="mt-1 text-xs text-bq-danger">{{ $message }}</p>@enderror
                </div>

                <p class="text-sm text-bq-text-muted">
                    Deskripsi bisnis ditampilkan pada pratinjau.
                    <a @click="confirmSection($event)" href="{{ route('owner.settings.business', ['tab' => 'profile']) }}" class="bq-link">Ubah di Profil bisnis</a>
                </p>

                <x-ui.savebar class="!static !px-0">
                    <x-ui.button variant="ghost" :href="route('owner.booking-page', ['section' => 'tampilan'])">Batal</x-ui.button>
                    <x-ui.button type="submit" variant="primary">Simpan tampilan</x-ui.button>
                </x-ui.savebar>
            </form>

            {{-- Live phone-sized preview (D-009), built from the customer components so it matches the storefront (BQ-165). --}}
            <section aria-label="Pratinjau di HP" class="lg:sticky lg:top-20 lg:self-start">
                <p class="mb-2 text-sm text-bq-text-muted">Pratinjau di HP</p>
                <div class="mx-auto w-full max-w-72 overflow-hidden rounded-[2rem] border-8 border-bq-ink bg-sf-page text-sf-text" x-bind:style="previewStyle" inert>
                    <div class="sf-hero px-3 pb-4 pt-3" x-bind:data-banner="image(look.cover_asset_id) ? '' : null">
                        <template x-if="image(look.cover_asset_id)">
                            <span><img x-bind:src="image(look.cover_asset_id)" alt="" class="sf-hero-banner"><span class="sf-hero-scrim"></span></span>
                        </template>
                        <x-customer.hero-ornament class="-right-16 -top-16 size-40" />
                        <div class="flex items-center gap-2">
                            <template x-if="image(look.logo_asset_id)"><img x-bind:src="image(look.logo_asset_id)" alt="" class="size-8 rounded-sf-card-small bg-sf-surface object-cover"></template>
                            <template x-if="!image(look.logo_asset_id)"><span class="grid size-8 place-items-center rounded-sf-card-small bg-sf-surface text-xs font-bold text-sf-primary-text">{{ mb_strtoupper(mb_substr($tenant->namabisnis ?: 'BQ', 0, 2)) }}</span></template>
                            <span class="text-[11px] font-semibold text-sf-hero-soft">Reservasi online</span>
                        </div>
                        <p class="mt-3 truncate text-lg font-bold" title="{{ $tenant->namabisnis }}">{{ $tenant->namabisnis }}</p>
                        @if ($appearance['deskripsi'])
                            <p class="mt-1 line-clamp-2 text-[11px] text-sf-hero-soft">{{ $appearance['deskripsi'] }}</p>
                        @endif
                    </div>
                    <div class="space-y-2 p-3">
                        <div class="flex gap-1.5">
                            <span class="sf-chip min-h-8 px-3 text-xs" aria-pressed="true">Semua</span>
                            <span class="sf-chip min-h-8 px-3 text-xs" aria-pressed="false">Kategori</span>
                        </div>
                        @forelse ($previewServices as $service)
                            {{-- Phone layout of x-customer.service-row; the component's breakpoints follow the screen, not this frame. --}}
                            <div class="sf-card-small flex items-center gap-3 p-2.5">
                                <span @class(['grid size-12 shrink-0 place-items-center rounded-sf-card-small text-sf-primary-text', 'bg-sf-tint' => $loop->even, 'bg-sf-tint-strong' => $loop->odd])>
                                    <x-customer.icon name="calendar" :size="20" :stroke="1.5" />
                                </span>
                                <span class="min-w-0 grow">
                                    <span class="block truncate text-[13px] font-bold" title="{{ $service['name'] }}">{{ $service['name'] }}</span>
                                    <span class="block text-[11px] text-sf-muted">{{ $service['duration'] }} menit</span>
                                    <span class="sf-num block text-[13px] font-bold text-sf-primary-text">{{ $service['price'] }}</span>
                                </span>
                            </div>
                        @empty
                            <p class="text-xs text-sf-muted">Belum ada layanan aktif.</p>
                        @endforelse
                        @if ($previewServices)
                            <span class="sf-btn sf-btn-sm w-full">Pilih jadwal</span>
                        @endif
                    </div>
                </div>
                <p class="mt-2 text-center text-bq-small text-bq-text-muted">Pratinjau memakai layanan Anda.</p>
            </section>
        </div>
    @endif

    {{-- ── Galeri media (FR-ASSET-005, AC-HBK-05) ── --}}
    @if ($activeSection === 'galeri')
        <section id="booking-gallery-upload" aria-labelledby="upload-title" class="{{ $card }} p-4">
            <h2 id="upload-title" class="text-base font-semibold text-bq-text">Tambah gambar</h2>
            <form method="POST" action="{{ route('owner.booking-page.media.store') }}" enctype="multipart/form-data" class="mt-3 grid gap-3 sm:grid-cols-2 sm:items-end lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_10rem_auto]" x-data="{ sending: false }" x-on:submit="sending = true">
                @csrf
                <div>
                    <label for="media-file" class="{{ $label }}">File gambar <span class="font-normal text-bq-text-muted">(maks. 10 MB)</span></label>
                    <input id="media-file" type="file" name="file" required accept="image/jpeg,image/png,image/webp,image/gif" class="mt-1 block w-full min-w-0 text-sm text-bq-text-muted">
                    @error('file')<p class="mt-1 text-xs text-bq-danger">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="media-title" class="{{ $label }}">Judul</label>
                    <input id="media-title" type="text" name="title" maxlength="150" value="{{ old('title') }}" class="{{ $field }}">
                </div>
                <div>
                    <label for="media-category" class="{{ $label }}">Kategori</label>
                    <select id="media-category" name="category" class="{{ $field }}">
                        @foreach ($categories as $key => $categoryLabel)
                            <option value="{{ $key }}" @selected(old('category', 'gallery') === $key)>{{ $categoryLabel }}</option>
                        @endforeach
                    </select>
                    @error('category')<p class="mt-1 text-xs text-bq-danger">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="{{ $buttonPrimary }}" x-bind:disabled="sending" x-text="sending ? 'Mengunggah…' : 'Unggah'">Unggah</button>
            </form>
        </section>

        @error('asset')
            <p role="alert" class="rounded-lg bg-bq-warning-bg px-4 py-3 text-sm text-bq-warning">{{ $message }}</p>
        @enderror

        @if (count($gallery) === 0)
            <x-owner.empty-state title="Galeri masih kosong" description="Semua gambar bisnis Anda tersimpan di sini: logo, sampul, dan gambar layanan." />
        @else
            <ul class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4" aria-label="Galeri media">
                @foreach ($gallery as $image)
                    <li class="{{ $card }} overflow-hidden">
                        <img src="{{ $image['url'] }}" alt="{{ $image['title'] }}" class="aspect-square w-full object-cover" loading="lazy">
                        <div class="space-y-1 p-3 text-sm">
                            <p class="truncate font-medium text-bq-text" title="{{ $image['title'] }}">{{ $image['title'] }}</p>
                            <p class="text-xs text-bq-text-muted">{{ $image['category'] }}@if ($image['size']) · {{ $image['size'] }}@endif</p>
                            @if ($image['usage'] !== [])
                                <p class="text-xs text-bq-text-muted">Dipakai: {{ implode(', ', $image['usage']) }}</p>
                            @endif
                            <button
                                type="button"
                                class="text-xs text-bq-danger hover:underline"
                                x-data
                                x-on:click="$dispatch('open-confirm', {
                                    title: 'Hapus gambar?',
                                    message: @js($image['usage'] !== [] ? 'Gambar ini dipakai sebagai '.implode(', ', $image['usage']).'. Jika dihapus, pemakaiannya ikut dilepas.' : 'Gambar ini akan dihapus dari galeri.'),
                                    actionUrl: @js(str_replace('__ID__', (string) $image['id'], $destroyUrl).($image['usage'] !== [] ? '?confirm=1' : '')),
                                    method: 'DELETE',
                                    trigger: $el,
                                })"
                            >Hapus</button>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    @endif

    {{-- ── Halaman depan (Pro, FR-PUBLIC-007, AC-HBK-03) ── --}}
    @if ($activeSection === 'landing')
        @if (! $isPro)
            <x-owner.upgrade-prompt feature="Halaman depan" plan="Pro" description="Buka halaman booking dengan judul, banner dan bagian pilihan Anda, dari tiga template siap pakai." />
        @else
            <form method="POST" action="{{ route('owner.booking-page.landing') }}" class="{{ $card }} space-y-5 p-4" x-data="{ sending: false }" x-on:submit="if ($event.submitter?.formTarget !== '_blank') sending = true">
                @csrf
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-base font-semibold text-bq-text">Halaman depan</h2>
                    <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $landing['published'] ? 'bg-bq-success-bg text-bq-success' : 'bg-bq-background text-bq-text-muted ring-1 ring-inset ring-bq-border' }}">{{ $landing['published'] ? 'Diterbitkan' : 'Belum diterbitkan' }}</span>
                </div>

                <fieldset>
                    <legend class="{{ $label }}">Template</legend>
                    <div class="mt-2 grid gap-2 sm:grid-cols-3">
                        @foreach ($landing['templates'] as $key => $templateLabel)
                            <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-bq-border p-3 text-sm text-bq-text has-[:checked]:border-bq-primary has-[:checked]:ring-2 has-[:checked]:ring-bq-primary/30">
                                <input type="radio" name="template" value="{{ $key }}" @checked(old('template', $landing['template']) === $key) class="text-bq-primary focus:ring-bq-primary">
                                {{ $templateLabel }}
                            </label>
                        @endforeach
                    </div>
                    @error('template')<p class="mt-1 text-xs text-bq-danger">{{ $message }}</p>@enderror
                </fieldset>

                <div>
                    <label for="landing-headline-input" class="{{ $label }}">Judul</label>
                    <input id="landing-headline-input" type="text" name="headline" maxlength="120" value="{{ old('headline', $landing['headline']) }}" placeholder="{{ $tenant->namabisnis }}" class="{{ $field }}">
                    <p class="mt-1 text-xs text-bq-text-muted">Kosongkan untuk memakai nama bisnis.</p>
                    @error('headline')<p class="mt-1 text-xs text-bq-danger">{{ $message }}</p>@enderror
                </div>

                <fieldset>
                    <legend class="{{ $label }}">Bagian yang ditampilkan</legend>
                    <div class="mt-2 flex flex-wrap gap-4">
                        @foreach ($landing['sectionLabels'] as $key => $sectionLabel)
                            <label class="inline-flex items-center gap-2 text-sm text-bq-text">
                                <input type="checkbox" name="sections[]" value="{{ $key }}" @checked(in_array($key, old('sections', $landing['sections']), true)) class="rounded text-bq-primary focus:ring-bq-primary">
                                {{ $sectionLabel }}
                            </label>
                        @endforeach
                    </div>
                    <p class="mt-1 text-xs text-bq-text-muted">Isi bagian diambil dari profil bisnis dan layanan aktif Anda.</p>
                    @error('sections.*')<p class="mt-1 text-xs text-bq-danger">{{ $message }}</p>@enderror
                </fieldset>

                <div>
                    <p class="{{ $label }}">Banner</p>
                    <x-owner.gallery-picker name="banner_asset_id" :images="$pickerImages" :selected="old('banner_asset_id', $landing['banner_asset_id'])" :gallery-url="route('owner.booking-page', ['section' => 'galeri'])" label="Pilih banner" class="mt-2" />
                    @error('banner_asset_id')<p class="mt-1 text-xs text-bq-danger">{{ $message }}</p>@enderror
                </div>

                <div class="flex flex-wrap items-center gap-2 border-t border-bq-border pt-4">
                    <button type="submit" name="action" value="save" class="{{ $buttonSecondary }}" x-bind:disabled="sending">Simpan</button>
                    <button type="submit" name="action" value="save" formaction="{{ route('owner.booking-page.landing.preview-form') }}" formtarget="_blank" class="{{ $buttonSecondary }}">Pratinjau</button>
                    @if ($landing['published'])
                        <button type="submit" name="action" value="publish" class="{{ $buttonPrimary }}" x-bind:disabled="sending">Simpan dan terbitkan</button>
                        <button type="submit" name="action" value="unpublish" class="{{ $buttonSecondary }}" x-bind:disabled="sending">Sembunyikan</button>
                    @else
                        <button type="submit" name="action" value="publish" class="{{ $buttonPrimary }}" x-bind:disabled="sending">Terbitkan</button>
                    @endif
                </div>
                <p class="text-xs text-bq-text-muted">Pratinjau membuka isian saat ini di tab baru.</p>
            </form>
        @endif
    @endif

    {{-- ── Domain sendiri (Pro, D-005, AC-HBK-03/04) ── --}}
    @if ($activeSection === 'domain')
        @if (! $isPro)
            <x-owner.upgrade-prompt feature="Domain sendiri" plan="Pro" description="Pakai alamat web bisnis Anda sendiri, misalnya booking.namabisnis.com, untuk halaman booking." />
        @else
            <div class="grid gap-6 lg:grid-cols-3">
                <section aria-labelledby="domain-title" class="{{ $card }} space-y-4 p-4 lg:col-span-2">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 id="domain-title" class="text-base font-semibold text-bq-text">Domain sendiri</h2>
                        @if ($domain['statusLabel'])
                            <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $domain['status'] === 'connected' ? 'bg-bq-success-bg text-bq-success' : ($domain['status'] === 'not_connected' ? 'bg-bq-danger-bg text-bq-danger' : 'bg-bq-warning-bg text-bq-warning') }}">{{ $domain['statusLabel'] }}</span>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('owner.booking-page.domain') }}" class="flex flex-col gap-2 sm:flex-row sm:items-end" x-data="{ sending: false }" x-on:submit="sending = true">
                        @csrf
                        <div class="flex-1">
                            <label for="custom-domain" class="{{ $label }}">Domain</label>
                            <input id="custom-domain" type="text" name="custom_domain" inputmode="url" autocomplete="off" value="{{ old('custom_domain', $domain['name']) }}" placeholder="booking.namabisnis.com" class="{{ $field }}">
                        </div>
                        <button type="submit" class="{{ $buttonPrimary }}" x-bind:disabled="sending">Simpan domain</button>
                    </form>
                    @error('custom_domain')<p class="text-xs text-bq-danger">{{ $message }}</p>@enderror

                    @if ($domain['name'] !== '')
                        <div class="flex flex-wrap items-center gap-3 border-t border-bq-border pt-4">
                            <form method="POST" action="{{ route('owner.booking-page.domain.check') }}" x-data="{ sending: false }" x-on:submit="sending = true">
                                @csrf
                                <button type="submit" class="{{ $buttonSecondary }}" x-bind:disabled="sending">Cek sekarang</button>
                            </form>
                            <p class="text-xs text-bq-text-muted">
                                @if ($domain['checkedAt'])
                                    Terakhir dicek {{ $domain['checkedAt'] }} WIB. Dicek otomatis setiap jam.
                                @else
                                    Sedang dicek. Dicek otomatis setiap jam.
                                @endif
                            </p>
                        </div>
                        @if (session()->has('domain_dns'))
                            <p class="text-sm text-bq-text-muted">DNS {{ $domain['name'] }} saat ini: {{ session('domain_dns') !== [] ? implode(', ', session('domain_dns')) : 'tidak ada catatan CNAME atau A' }}.</p>
                        @endif
                        @if ($domain['status'] === 'connected')
                            <p class="text-sm text-bq-text">Pelanggan bisa membuka halaman booking Anda di <a href="https://{{ $domain['name'] }}" target="_blank" rel="noopener" class="font-medium bq-link">{{ $domain['name'] }}</a>.</p>
                        @endif
                    @endif
                </section>

                <section aria-labelledby="dns-title" class="{{ $card }} space-y-3 p-4">
                    <h2 id="dns-title" class="text-base font-semibold text-bq-text">Petunjuk DNS</h2>
                    @if (! $domain['configured'])
                        <p class="text-sm text-bq-text-muted">Tujuan DNS BookQu belum diatur. Hubungi tim BookQu sebelum menghubungkan domain.</p>
                    @else
                        <p class="text-sm text-bq-text-muted">Buka pengaturan DNS di tempat Anda membeli domain, lalu tambahkan:</p>
                        @if ($domain['cnameTarget'] !== '')
                            <div class="rounded-lg bg-bq-background p-3 text-sm">
                                <p class="font-medium text-bq-text">Subdomain (mis. booking.namabisnis.com)</p>
                                <p class="mt-1 text-bq-text-muted">Tipe <strong class="text-bq-text">CNAME</strong> ke <code class="break-all text-bq-text">{{ $domain['cnameTarget'] }}</code> <button type="button" x-data="ownerCopyText(@js($domain['cnameTarget']))" @click="copy()" class="ms-1 bq-link" x-text="copied ? 'Disalin' : 'Salin tujuan CNAME'">Salin tujuan CNAME</button></p>
                            </div>
                        @endif
                        @if ($domain['addresses'] !== [])
                            <div class="rounded-lg bg-bq-background p-3 text-sm">
                                <p class="font-medium text-bq-text">Domain utama (mis. namabisnis.com)</p>
                                <ul class="mt-1 space-y-1 text-bq-text-muted">
                                    @foreach ($domain['addresses'] as $address)
                                        <li>A ke <code class="text-bq-text">{{ $address }}</code>
                                            <button type="button" x-data="ownerCopyText(@js($address))" @click="copy()" class="ms-1 bq-link" x-text="copied ? 'Disalin' : 'Salin alamat A'">Salin alamat A</button>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        @if ($domain['txtValue'] !== '')
                            <div class="rounded-lg bg-bq-background p-3 text-sm">
                                <p class="font-medium text-bq-text">Bukti kepemilikan (wajib)</p>
                                <p class="mt-1 text-bq-text-muted">Tipe <strong class="text-bq-text">TXT</strong> dengan nama <code class="break-all text-bq-text">{{ $domain['txtHost'] }}</code> <button type="button" x-data="ownerCopyText(@js($domain['txtHost']))" @click="copy()" class="ms-1 bq-link" x-text="copied ? 'Disalin' : 'Salin nama TXT'">Salin nama TXT</button> berisi <code class="break-all text-bq-text">{{ $domain['txtValue'] }}</code> <button type="button" x-data="ownerCopyText(@js($domain['txtValue']))" @click="copy()" class="ms-1 bq-link" x-text="copied ? 'Disalin' : 'Salin nilai TXT'">Salin nilai TXT</button></p>
                            </div>
                        @endif
                        <p class="text-xs text-bq-text-muted">Perubahan DNS bisa butuh beberapa jam. Halaman booking baru terbuka di domain Anda setelah statusnya Terhubung.</p>
                    @endif
                </section>
            </div>
        @endif
    @endif
</div>
@endsection
