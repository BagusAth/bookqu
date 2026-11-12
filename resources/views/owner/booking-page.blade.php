@extends('layouts.owner-layout')

@section('title', 'Halaman Booking')

{{--
    Halaman Booking (BQ-142, owner-portal.md §8.9): Link & QR, Materi promosi, Tampilan and Galeri
    media, for every plan (D-009, D-010), plus Landing page and Domain sendiri for Pro (BQ-154), which
    show the upgrade prompt below Pro (§3 rule 6). Data from App\Actions\Tenant\GetBookingPageData.
--}}

@php
    $card = 'rounded-xl border border-bq-border bg-bq-surface';
    $buttonSecondary = 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-bq-border bg-bq-surface px-3 py-1.5 text-sm font-medium text-bq-text hover:bg-bq-background';
    $buttonPrimary = 'inline-flex items-center justify-center gap-1.5 rounded-lg bg-bq-primary px-4 py-2 text-sm font-medium text-white hover:bg-bq-primary-hover';
    $field = 'mt-1 block w-full rounded-lg border border-bq-border bg-bq-surface px-3 py-2 text-sm text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/30';
    $label = 'block text-sm font-medium text-bq-text';
    $destroyUrl = route('owner.booking-page.media.destroy', ['asset' => '__ID__']);
    $sections = collect(\App\Actions\Tenant\GetBookingPageData::SECTIONS);
@endphp

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <x-owner.page-header title="Halaman Booking" purpose="Bagaimana pelanggan menemukan Anda, dan apa yang mereka lihat." />

    <x-owner.tabs :tabs="$sections->all()" param="section" :active="$activeSection" label="Bagian Halaman Booking" />

    {{-- ── Link & QR (FR-PUBLIC-006, AC-HBK-02) ── --}}
    @if ($activeSection === 'link')
        <div class="grid gap-6 lg:grid-cols-3">
            <section aria-labelledby="link-title" class="{{ $card }} p-4 lg:col-span-2" x-data="ownerBookingLink(@js(['url' => $link, 'share_text' => 'Booking '.($tenant->namabisnis ?: 'kami').' online: '.$link]), @js(route('owner.dashboard.link-shared')))">
                <h2 id="link-title" class="text-base font-semibold text-bq-text">Link booking</h2>
                <p class="mt-1 text-sm text-bq-text-muted">Link ini membuka halaman booking Anda. Pasang di bio Instagram, Google Maps, atau kirim lewat WhatsApp.</p>
                <p class="mt-3 break-all rounded-lg bg-bq-background px-3 py-2 font-mono text-sm text-bq-text">{{ $link }}</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <button type="button" class="{{ $buttonPrimary }}" x-on:click="copy()" x-text="copied ? 'Tersalin' : 'Salin link'">Salin link</button>
                    <a href="{{ $link }}" target="_blank" rel="noopener" class="{{ $buttonSecondary }}">Buka halaman</a>
                    <a href="{{ $whatsappShareUrl }}" target="_blank" rel="noopener" x-on:click="mark()" class="{{ $buttonSecondary }}">Bagikan ke WhatsApp</a>
                </div>
            </section>

            <section aria-labelledby="qr-title" class="{{ $card }} p-4">
                <h2 id="qr-title" class="text-base font-semibold text-bq-text">Kode QR</h2>
                <div class="mx-auto mt-3 w-44 [&>svg]:h-auto [&>svg]:w-full" role="img" aria-label="Kode QR untuk {{ $link }}">{!! $qrSvg !!}</div>
                <div class="mt-3 flex flex-wrap justify-center gap-2">
                    <a href="{{ route('owner.booking-page.qr') }}" class="{{ $buttonSecondary }}">Unduh QR</a>
                    <a href="{{ route('owner.booking-page.poster') }}" target="_blank" rel="noopener" class="{{ $buttonSecondary }}">Cetak poster</a>
                </div>
            </section>
        </div>
    @endif

    {{-- ── Materi promosi ── --}}
    @if ($activeSection === 'promosi')
        <div class="grid gap-4 lg:grid-cols-2">
            @foreach ([['Bio Instagram', 'instagram', 'Salin teks bio Instagram'], ['Pesan WhatsApp', 'whatsapp', 'Salin pesan WhatsApp']] as [$title, $key, $action])
                <section class="{{ $card }} p-4" x-data="ownerCopyText(@js($materials[$key]))">
                    <h2 class="text-base font-semibold text-bq-text">{{ $title }}</h2>
                    <p class="mt-2 whitespace-pre-line rounded-lg bg-bq-background px-3 py-2 text-sm text-bq-text">{{ $materials[$key] }}</p>
                    <button type="button" class="{{ $buttonSecondary }} mt-3" x-on:click="copy()" x-text="copied ? 'Tersalin' : @js($action)">{{ $action }}</button>
                </section>
            @endforeach
        </div>
    @endif

    {{-- ── Tampilan (FR-PUBLIC-003, AC-HBK-01) ── --}}
    @if ($activeSection === 'tampilan')
        <div
            class="grid gap-6 lg:grid-cols-[1fr_20rem]"
            x-data="{
                look: @js(['theme_color' => old('theme_color', $appearance['theme_color']), 'button_style' => old('button_style', $appearance['button_style']), 'card_style' => old('card_style', $appearance['card_style']), 'font_family' => old('font_family', $appearance['font_family']), 'deskripsi' => old('deskripsi', $appearance['deskripsi']), 'logo_asset_id' => (string) old('logo_asset_id', $appearance['logo_asset_id'] ?? ''), 'cover_asset_id' => (string) old('cover_asset_id', $appearance['cover_asset_id'] ?? '')]),
                images: @js($pickerImages),
                radius: @js(collect($branding['buttons'])->map(fn ($style) => $style['radius'])),
                cards: @js($branding['cards']),
                image(id) { const found = this.images.find(i => String(i.id) === String(id)); return found ? found.url : null; },
            }"
        >
            <form method="POST" action="{{ route('owner.booking-page.appearance') }}" class="{{ $card }} space-y-5 p-4">
                @csrf
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
                        <input id="theme-color" type="text" name="theme_color" x-model="look.theme_color" maxlength="7" class="w-28 rounded-lg border border-bq-border px-3 py-1.5 font-mono text-sm uppercase">
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

                <div>
                    <label for="business-description" class="{{ $label }}">Deskripsi <span class="font-normal text-bq-text-muted">(tampil di halaman booking)</span></label>
                    <textarea id="business-description" name="deskripsi" rows="3" maxlength="1000" x-model="look.deskripsi" class="{{ $field }}"></textarea>
                    @error('deskripsi')<p class="mt-1 text-xs text-bq-danger">{{ $message }}</p>@enderror
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="{{ $buttonPrimary }}">Simpan tampilan</button>
                </div>
            </form>

            {{-- Live phone-sized preview (D-009) --}}
            <section aria-label="Pratinjau di HP" class="lg:sticky lg:top-20 lg:self-start">
                <p class="mb-2 text-sm text-bq-text-muted">Pratinjau di HP</p>
                <div class="mx-auto w-72 overflow-hidden rounded-[2rem] border-8 border-bq-ink bg-white shadow-lg" x-bind:style="'font-family: \'' + look.font_family + '\', system-ui, sans-serif'">
                    <div class="flex items-center gap-2 border-b border-slate-200 px-3 py-2">
                        <template x-if="image(look.logo_asset_id)"><img x-bind:src="image(look.logo_asset_id)" alt="" class="h-7 w-7 rounded-lg object-cover"></template>
                        <template x-if="!image(look.logo_asset_id)"><span class="flex h-7 w-7 items-center justify-center rounded-lg text-xs font-bold text-white" x-bind:style="'background-color:' + look.theme_color">{{ mb_strtoupper(mb_substr($tenant->namabisnis ?: 'BQ', 0, 2)) }}</span></template>
                        <span class="truncate text-sm font-semibold text-slate-900">{{ $tenant->namabisnis }}</span>
                    </div>
                    <template x-if="image(look.cover_asset_id)"><img x-bind:src="image(look.cover_asset_id)" alt="" class="h-24 w-full object-cover"></template>
                    <p x-show="look.deskripsi" class="whitespace-pre-line border-b border-slate-100 px-3 py-2 text-xs text-slate-600" x-text="look.deskripsi"></p>
                    <div class="space-y-2 bg-slate-50 p-3">
                        <div class="rounded-xl bg-white p-3" x-bind:style="'box-shadow:' + (cards[look.card_style] || cards.elevated).shadow + ';border:' + (cards[look.card_style] || cards.elevated).border">
                            <p class="text-sm font-semibold text-slate-900">Contoh layanan</p>
                            <p class="text-xs text-slate-500">Rp 150.000 / sesi · 60 menit</p>
                            <span class="mt-2 inline-block px-3 py-1.5 text-xs font-semibold text-white" x-bind:style="'background-color:' + look.theme_color + ';border-radius:' + (radius[look.button_style] || '0.75rem')">Pilih jadwal</span>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    @endif

    {{-- ── Galeri media (FR-ASSET-005, AC-HBK-05) ── --}}
    @if ($activeSection === 'galeri')
        <section aria-labelledby="upload-title" class="{{ $card }} p-4">
            <h2 id="upload-title" class="text-base font-semibold text-bq-text">Tambah gambar</h2>
            <form method="POST" action="{{ route('owner.booking-page.media.store') }}" enctype="multipart/form-data" class="mt-3 grid gap-3 sm:grid-cols-[1fr_12rem_10rem_auto] sm:items-end" x-data="{ sending: false }" x-on:submit="sending = true">
                @csrf
                <div>
                    <label for="media-file" class="{{ $label }}">File gambar <span class="font-normal text-bq-text-muted">(maks. 10 MB)</span></label>
                    <input id="media-file" type="file" name="file" required accept="image/jpeg,image/png,image/webp,image/gif" class="mt-1 block w-full text-sm text-bq-text-muted">
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

    {{-- ── Landing page (Pro, FR-PUBLIC-007, AC-HBK-03) ── --}}
    @if ($activeSection === 'landing')
        @if (! $isPro)
            <x-owner.upgrade-prompt feature="Landing page" plan="Pro" description="Buka halaman booking dengan judul, banner dan bagian pilihan Anda, dari tiga template siap pakai." />
        @else
            <form method="POST" action="{{ route('owner.booking-page.landing') }}" class="{{ $card }} space-y-5 p-4" x-data="{ sending: false }" x-on:submit="sending = true">
                @csrf
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-base font-semibold text-bq-text">Landing page</h2>
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
                    <a href="{{ route('owner.booking-page.landing.preview') }}" target="_blank" rel="noopener" class="{{ $buttonSecondary }}">Pratinjau</a>
                    @if ($landing['published'])
                        <button type="submit" name="action" value="publish" class="{{ $buttonPrimary }}" x-bind:disabled="sending">Simpan dan terbitkan</button>
                        <button type="submit" name="action" value="unpublish" class="{{ $buttonSecondary }}" x-bind:disabled="sending">Sembunyikan</button>
                    @else
                        <button type="submit" name="action" value="publish" class="{{ $buttonPrimary }}" x-bind:disabled="sending">Terbitkan</button>
                    @endif
                </div>
                <p class="text-xs text-bq-text-muted">Pratinjau menampilkan versi yang terakhir disimpan.</p>
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
                            <p class="text-sm text-bq-text">Pelanggan bisa membuka halaman booking Anda di <a href="https://{{ $domain['name'] }}" target="_blank" rel="noopener" class="font-medium text-bq-primary hover:text-bq-primary-hover">{{ $domain['name'] }}</a>.</p>
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
                                <p class="mt-1 text-bq-text-muted">Tipe <strong class="text-bq-text">CNAME</strong> ke <code class="break-all text-bq-text">{{ $domain['cnameTarget'] }}</code></p>
                            </div>
                        @endif
                        @if ($domain['addresses'] !== [])
                            <div class="rounded-lg bg-bq-background p-3 text-sm">
                                <p class="font-medium text-bq-text">Domain utama (mis. namabisnis.com)</p>
                                <p class="mt-1 text-bq-text-muted">Tipe <strong class="text-bq-text">A</strong> ke <code class="text-bq-text">{{ implode(', ', $domain['addresses']) }}</code></p>
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
