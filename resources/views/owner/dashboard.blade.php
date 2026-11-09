@extends('layouts.owner-layout')

@section('title', 'Beranda')

{{--
    Beranda (BQ-136, owner-portal.md §8.1): one banner, today in four figures, "Perlu tindakan",
    today's schedule with tomorrow's summary, the booking link card, quick actions, and the new
    owner guide in place of sections 2–4 until it is done. Data from App\Actions\Dashboard\GetBeranda;
    sections 2–4 refresh while the page is visible and render as text only. No charts (AC-BRD-07).
    Visual style follows the former dashboard (stat tiles with icons, section cards, ring badges).
--}}

@php
    $bannerTone = [
        'danger' => ['box' => 'border-rose-200 bg-rose-50', 'title' => 'text-rose-900', 'text' => 'text-rose-800', 'button' => 'bg-rose-600 hover:bg-rose-700'],
        'warning' => ['box' => 'border-amber-200 bg-amber-50', 'title' => 'text-amber-900', 'text' => 'text-amber-800', 'button' => 'bg-amber-600 hover:bg-amber-700'],
        'info' => ['box' => 'border-indigo-200 bg-indigo-50', 'title' => 'text-indigo-900', 'text' => 'text-indigo-800', 'button' => 'bg-indigo-600 hover:bg-indigo-700'],
    ];
    $badgeTone = "{ 'bg-emerald-50 text-emerald-700 ring-emerald-600/20': badge.tone === 'success', 'bg-indigo-50 text-indigo-700 ring-indigo-600/20': badge.tone === 'info', 'bg-amber-50 text-amber-700 ring-amber-600/20': badge.tone === 'warning', 'bg-rose-50 text-rose-700 ring-rose-600/20': badge.tone === 'danger', 'bg-gray-50 text-gray-700 ring-gray-600/20': !['info','success','warning','danger'].includes(badge.tone) }";
    $sectionCard = 'rounded-xl border border-bq-border bg-bq-surface';
    $sectionHeader = 'flex items-center justify-between border-b border-bq-border px-4 py-3.5 sm:px-5 sm:py-4';
    $secondaryButton = 'craft-btn inline-flex items-center justify-center gap-1.5 rounded-xl border border-[#e7e2f7] bg-white px-3 py-2 text-xs font-bold text-[#6e6584] shadow-2xs transition hover:bg-[#f3effe] hover:text-[#382186]';
    $primaryButton = 'craft-btn inline-flex items-center justify-center gap-2 rounded-xl bg-bq-primary px-4 py-2.5 text-xs sm:text-sm font-semibold text-white shadow-md shadow-bq-primary/25 transition-all hover:bg-bq-primary-hover hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0';
    $figureTiles = [
        'bookings' => 'bg-indigo-50 text-indigo-600',
        'waiting' => 'bg-amber-50 text-amber-600',
        'revenue' => 'bg-orange-50 text-orange-500',
        'occupancy' => 'bg-sky-50 text-sky-600',
    ];
    $figureIcons = [
        'bookings' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        'waiting' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        'revenue' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'occupancy' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
    ];
    $quickIcons = [
        'walkin' => 'M12 4v16m8-8H4',
        'service' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10',
        'promo' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z',
        'team' => 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z',
    ];
    $greetingName = \Illuminate\Support\Str::of((string) (auth()->user()?->namalengkap ?? ''))->before(' ')->value();
@endphp

@section('content')
<div class="mx-auto max-w-7xl space-y-4 sm:space-y-6">

    {{-- ── Welcome header ── --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-bq-text sm:text-2xl lg:text-3xl" id="welcome-heading">
                {{ $greetingName !== '' ? 'Halo, '.$greetingName : 'Beranda' }}
            </h1>
            <p class="mt-0.5 text-xs text-bq-text-muted sm:mt-1 sm:text-sm">Yang perlu Anda ketahui dan lakukan hari ini.</p>
        </div>
    </div>

    {{-- 1. One status banner (AC-BRD-01) --}}
    @if ($banner)
        @php $tone = $bannerTone[$banner['tone']] ?? $bannerTone['info']; @endphp
        <div role="status" data-beranda-banner="{{ $banner['kind'] }}" class="rounded-xl border p-4 sm:px-5 sm:py-4 {{ $tone['box'] }}">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-semibold {{ $tone['title'] }}">{{ $banner['message'] }}</p>
                    @if ($banner['note'])
                        <p class="text-xs {{ $tone['text'] }}">{{ $banner['note'] }}</p>
                    @endif
                </div>
                @if ($banner['action'])
                    <a href="{{ $banner['action']['url'] }}" class="craft-btn inline-flex w-full shrink-0 items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-xs font-semibold text-white transition-all active:scale-95 sm:w-auto sm:py-2 {{ $tone['button'] }}">{{ $banner['action']['label'] }}</a>
                @endif
            </div>
        </div>
    @endif

    @if ($guide)
        {{-- 7. New owner guide (AC-BRD-05), shown instead of sections 2–4 --}}
        @php $guidePercent = (int) round($guide['done'] / 4 * 100); @endphp
        <section data-owner-guide aria-labelledby="owner-guide-title" class="rounded-2xl border border-[#e7e2f7] bg-white p-5 shadow-[0_4px_20px_rgba(35,26,61,0.03)] sm:p-6">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-[#b499ff]/20 bg-[#f3effe] text-[#382186]" aria-hidden="true">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <h2 id="owner-guide-title" class="text-base font-extrabold text-[#231a3d]">Siapkan bisnis Anda</h2>
                        <p class="text-[11px] font-bold tabular-nums text-[#6e6584]">{{ $guide['done'] }} dari 4 langkah</p>
                    </div>
                    <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-[#f3effe]" aria-hidden="true">
                        <div class="h-full rounded-full bg-[#382186]" style="width: {{ $guidePercent }}%"></div>
                    </div>
                </div>
            </div>
            <ol class="mt-5 space-y-2.5">
                @foreach ($guide['steps'] as $index => $step)
                    <li @class([
                        'flex items-center gap-3 rounded-xl border p-3.5',
                        'border-emerald-200 bg-emerald-50/50' => $step['done'],
                        'border-[#e7e2f7] bg-white' => ! $step['done'],
                    ])>
                        <span @class([
                            'flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-black',
                            'bg-emerald-500 text-white' => $step['done'],
                            'bg-[#f3effe] text-[#382186] ring-1 ring-inset ring-[#e7e2f7]' => ! $step['done'],
                        ]) aria-hidden="true">{{ $step['done'] ? '✓' : $index + 1 }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-bold {{ $step['done'] ? 'text-emerald-800 line-through' : 'text-[#231a3d]' }}">
                                {{ $step['label'] }}<span class="sr-only">{{ $step['done'] ? ' (selesai)' : ' (belum)' }}</span>
                            </p>
                            <p class="text-[11px] font-semibold text-[#6e6584]">{{ $step['hint'] }}</p>
                        </div>
                        @unless ($step['done'])
                            @if ($step['event'])
                                <button type="button" x-data x-on:click="$dispatch(@js($step['event']))" class="{{ $secondaryButton }} shrink-0">Mulai</button>
                            @elseif ($step['url'])
                                <a href="{{ $step['url'] }}" class="{{ $secondaryButton }} shrink-0">Mulai</a>
                            @endif
                        @endunless
                    </li>
                @endforeach
            </ol>
        </section>
    @else
        {{-- Sections 2–4, refreshed while visible (AC-BRD-06) --}}
        <div x-data="ownerBeranda(@js($live), @js(route('owner.dashboard.polling')))" class="space-y-4 sm:space-y-6">
            {{-- 2. Today in four figures --}}
            <section aria-label="Hari ini" x-data="{ tiles: @js($figureTiles), icons: @js($figureIcons) }" class="grid grid-cols-2 gap-2.5 sm:gap-4 lg:grid-cols-4">
                <template x-for="figure in live.figures" x-bind:key="figure.key">
                    <div class="group rounded-xl border border-bq-border bg-bq-surface p-3.5 transition-all duration-300 hover:border-bq-border-strong hover:shadow-md sm:p-5">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg transition-transform duration-300 group-hover:scale-110 sm:h-10 sm:w-10 sm:rounded-xl" x-bind:class="tiles[figure.key] || 'bg-gray-50 text-gray-600'" aria-hidden="true">
                            <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" x-bind:d="icons[figure.key] || ''"/></svg>
                        </div>
                        <div class="mt-2.5 sm:mt-4">
                            <p class="text-[10px] font-semibold uppercase leading-snug tracking-wide text-bq-text-muted sm:text-xs" x-text="figure.label"></p>
                            <p class="mt-0.5 break-words text-base font-extrabold leading-tight tracking-tight text-bq-text tabular-nums min-[400px]:text-lg sm:mt-1 sm:text-2xl" x-text="figure.value"></p>
                            <p class="mt-0.5 text-[10px] text-bq-text-subtle sm:text-[11px]" x-text="figure.hint"></p>
                        </div>
                    </div>
                </template>
            </section>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-5">
                {{-- 4. Today's schedule and tomorrow --}}
                <section aria-labelledby="today-title" class="{{ $sectionCard }} flex flex-col lg:col-span-3">
                    <div class="{{ $sectionHeader }}">
                        <div>
                            <h2 id="today-title" class="text-sm font-semibold text-bq-text sm:text-base">Jadwal hari ini</h2>
                            <p class="text-[11px] text-bq-text-muted sm:text-xs">Booking hari ini menurut jamnya</p>
                        </div>
                        <a href="{{ route('owner.calendar') }}" class="text-xs font-semibold text-bq-primary transition-colors hover:text-bq-primary-hover sm:text-sm">Kalender &rarr;</a>
                    </div>

                    <div x-show="live.today.length === 0" class="px-4 py-8 text-center">
                        <p class="text-sm font-semibold text-bq-text">Belum ada booking hari ini</p>
                        <p x-show="live.next" class="mt-1 text-xs text-bq-text-muted">
                            Booking berikutnya: <span class="font-semibold text-bq-text" x-text="live.next ? live.next.date + ', ' + live.next.time + ' · ' + live.next.customer : ''"></span>
                        </p>
                    </div>

                    <ul x-show="live.today.length > 0" class="divide-y divide-bq-border">
                        <template x-for="row in live.today" x-bind:key="row.id">
                            <li class="flex items-start gap-3 px-4 py-3.5 transition hover:bg-bq-background/40 sm:px-5" x-bind:class="row.in_progress ? 'bg-indigo-50/60' : ''">
                                <div class="w-24 shrink-0">
                                    <p class="text-xs font-bold tabular-nums text-bq-text sm:text-sm" x-text="row.time"></p>
                                    <p x-show="row.in_progress" class="mt-0.5 inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wide text-indigo-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-indigo-600" aria-hidden="true"></span>Sedang berlangsung
                                    </p>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="break-words text-xs font-bold text-bq-text sm:text-sm" x-text="row.customer"></p>
                                    <p class="truncate text-[11px] text-bq-text-muted sm:text-xs" x-text="row.service"></p>
                                    <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                        <template x-for="badge in [row.status, row.payment]">
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase ring-1 ring-inset" x-bind:class="{{ $badgeTone }}" x-text="badge.label"></span>
                                        </template>
                                    </div>
                                </div>
                                <div class="flex shrink-0 flex-col items-end gap-2">
                                    <a x-bind:href="row.href" x-on:click="if (row.panel_url) { $event.preventDefault(); $dispatch('open-booking-panel', { url: row.panel_url, trigger: $el }); }" class="{{ $secondaryButton }}">Buka</a>
                                    <form x-show="row.complete_url" method="POST" x-bind:action="row.complete_url">
                                        @csrf
                                        <input type="hidden" name="_method" value="PATCH">
                                        <input type="hidden" name="status" value="completed">
                                        <button type="submit" class="craft-btn inline-flex items-center rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700 transition hover:bg-emerald-100">Tandai selesai</button>
                                    </form>
                                </div>
                            </li>
                        </template>
                    </ul>

                    <p class="mt-auto border-t border-bq-border bg-bq-background/40 px-4 py-3 text-xs text-bq-text-muted sm:px-5">
                        <span class="font-bold text-bq-text">Besok:</span>
                        <span x-text="live.tomorrow.count === 0 ? 'belum ada booking' : live.tomorrow.count + ' booking, pertama ' + live.tomorrow.first_time + ' · ' + live.tomorrow.first_customer"></span>
                    </p>
                </section>

                {{-- 3. Perlu tindakan (AC-BRD-04) --}}
                <section aria-labelledby="actions-title" class="{{ $sectionCard }} lg:col-span-2">
                    <div class="{{ $sectionHeader }}">
                        <div>
                            <h2 id="actions-title" class="text-sm font-semibold text-bq-text sm:text-base">Perlu tindakan</h2>
                            <p class="text-[11px] text-bq-text-muted sm:text-xs">Yang menunggu keputusan Anda</p>
                        </div>
                    </div>
                    <div x-show="live.actions.length === 0" class="flex flex-col items-center px-4 py-8 text-center">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600" aria-hidden="true">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <p class="mt-2 text-xs text-bq-text-muted">Tidak ada yang perlu ditindak. Semua beres.</p>
                    </div>
                    <ul x-show="live.actions.length > 0" class="divide-y divide-bq-border">
                        <template x-for="item in live.actions" x-bind:key="item.key">
                            <li>
                                <a x-bind:href="item.url" class="flex items-center justify-between gap-3 px-4 py-3.5 text-xs font-semibold text-bq-text transition hover:bg-bq-background/50 sm:px-5 sm:text-sm">
                                    <span x-text="item.label"></span>
                                    <span class="inline-flex min-w-7 items-center justify-center rounded-full bg-amber-50 px-2 py-0.5 text-xs font-bold tabular-nums text-amber-700 ring-1 ring-inset ring-amber-600/20" x-text="item.count"></span>
                                </a>
                            </li>
                        </template>
                    </ul>
                </section>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-5">
        {{-- 5. Booking link card --}}
        @if ($link)
            <section aria-labelledby="link-title" class="{{ $sectionCard }} p-4 sm:p-5 lg:col-span-3" x-data="ownerBookingLink(@js($link), @js(route('owner.dashboard.link-shared')))">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#EEF2FF] text-[#4F46E5]" aria-hidden="true">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                    </div>
                    <div class="min-w-0">
                        <h2 id="link-title" class="text-sm font-semibold text-bq-text sm:text-base">Link booking Anda</h2>
                        <p class="text-[11px] text-bq-text-muted sm:text-xs">Bagikan ke pelanggan lewat WhatsApp, Instagram atau Google Maps.</p>
                    </div>
                </div>
                <p class="mt-3 break-all rounded-xl border border-bq-border bg-bq-background/60 px-3.5 py-2.5 font-mono text-xs text-bq-text sm:text-sm">{{ $link['url'] }}</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <button type="button" x-on:click="copy()" class="{{ $primaryButton }}" x-text="copied ? 'Tersalin' : 'Salin link'">Salin link</button>
                    <button type="button" x-show="canShare" x-cloak x-on:click="share()" class="{{ $secondaryButton }} py-2.5">Bagikan</button>
                    <a href="https://wa.me/?text={{ rawurlencode($link['share_text']) }}" target="_blank" rel="noopener" x-on:click="mark()" class="craft-btn inline-flex items-center gap-1.5 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2.5 text-xs font-bold text-emerald-700 transition hover:bg-emerald-100">Kirim lewat WhatsApp</a>
                </div>
            </section>
        @endif

        {{-- 6. Quick actions --}}
        @if (count($quickActions) > 0)
            <section aria-labelledby="quick-title" class="{{ $sectionCard }} p-4 sm:p-5 lg:col-span-2">
                <h2 id="quick-title" class="text-sm font-semibold text-bq-text sm:text-base">Aksi cepat</h2>
                <div class="mt-3 grid grid-cols-2 gap-2">
                    @foreach ($quickActions as $action)
                        @php $quickClass = 'craft-btn flex items-center gap-2 rounded-xl border border-[#e7e2f7] bg-white px-3 py-2.5 text-xs font-bold text-[#231a3d] shadow-2xs transition hover:border-[#b499ff] hover:bg-[#f3effe] hover:text-[#382186]'; @endphp
                        @php $quickPath = $quickIcons[$action['key']] ?? $quickIcons['walkin']; @endphp
                        @if ($action['event'] !== '')
                            <button type="button" data-quick-action="{{ $action['key'] }}" x-data x-on:click="$dispatch('open-dialog', { id: @js($action['event']), trigger: $el })" class="{{ $quickClass }}">
                                <svg class="h-4 w-4 shrink-0 text-[#382186]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $quickPath }}"/></svg>
                                <span class="truncate">+ {{ $action['label'] }}</span>
                            </button>
                        @else
                            <a href="{{ $action['url'] }}" data-quick-action="{{ $action['key'] }}" class="{{ $quickClass }}">
                                <svg class="h-4 w-4 shrink-0 text-[#382186]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $quickPath }}"/></svg>
                                <span class="truncate">+ {{ $action['label'] }}</span>
                            </a>
                        @endif
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</div>

@if ($profileIncomplete || $errors->any())
    @include('components.owner.modal-complete-profile', ['showProfilePrompt' => false])
@endif
@endsection
