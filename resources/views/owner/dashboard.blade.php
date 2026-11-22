@extends('layouts.owner-layout')

@section('title', 'Beranda')

{{--
    Beranda (BQ-136, owner-portal.md §8.1): one banner, today in four figures, "Perlu tindakan",
    today's schedule with tomorrow's summary, the booking link card, quick actions, and the new
    owner guide in place of sections 2–4 until it is done. Data from App\Actions\Dashboard\GetBeranda;
    sections 2–4 refresh while the page is visible and render as text only. No charts (AC-BRD-07).
    BQ-161: today's schedule is the day rail with the marigold "now" line; the first figure is the hero.
--}}

@php
    $card = 'bq-panel';
    $secondaryButton = 'bq-btn bq-btn-secondary bq-btn-sm';
    $localNow = now('Asia/Jakarta')->locale('id');
    $todayLabel = $localNow->translatedFormat('l, j M');
@endphp

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <x-owner.page-header title="Beranda" :purpose="$todayLabel.' · pukul '.$localNow->format('H.i')" />

    @if ($banner)
        <x-ui.banner :title="$banner['message']" :description="$banner['note']" :tone="$banner['tone']"
            :data-beranda-banner="$banner['kind']">
            @if ($banner['action'])
                <x-slot:action>
                    <x-ui.button :href="$banner['action']['url']" size="sm">{{ $banner['action']['label'] }}</x-ui.button>
                </x-slot:action>
            @endif
        </x-ui.banner>
    @endif

    <div class="grid items-start gap-5 lg:grid-cols-[minmax(0,2fr)_minmax(280px,1fr)]"
        @unless ($guide) x-data="ownerBeranda(@js($live), @js(route('owner.dashboard.polling')))" @endunless>
        @if ($guide)
            {{-- 7. New owner guide (AC-BRD-05), shown instead of sections 2–4 --}}
            <section data-owner-guide aria-labelledby="owner-guide-title" class="{{ $card }} overflow-hidden lg:col-span-2">
                <div class="flex items-center justify-between gap-3 border-b border-bq-border bg-bq-primary-light/60 px-5 py-4">
                    <div>
                        <h2 id="owner-guide-title" class="text-lg font-bold text-bq-ink">Siapkan bisnis Anda</h2>
                        <p class="mt-0.5 text-sm text-bq-text-muted">Empat langkah sampai pelanggan pertama bisa booking.</p>
                    </div>
                    <div class="text-right">
                        <p class="bq-num text-sm font-semibold text-bq-primary">{{ $guide['done'] }} dari 4 langkah</p>
                        <div class="mt-1.5 h-1.5 w-28 overflow-hidden rounded-full bg-white" aria-hidden="true">
                            <div class="h-full rounded-full bg-bq-primary" style="width: {{ (int) round($guide['done'] / 4 * 100) }}%"></div>
                        </div>
                    </div>
                </div>
                <ol class="divide-y divide-bq-border">
                    @php $nextStep = collect($guide['steps'])->search(fn ($step) => ! $step['done']); @endphp
                    @foreach ($guide['steps'] as $index => $step)
                        <li @class(['flex items-start gap-3 px-5 py-4', 'bg-bq-accent-bg/50' => $index === $nextStep])>
                            <span @class([
                                'flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-bold',
                                'bg-bq-primary text-white' => $step['done'],
                                'bg-bq-accent text-bq-ink' => ! $step['done'] && $index === $nextStep,
                                'bg-bq-surface-2 text-bq-text-muted' => ! $step['done'] && $index !== $nextStep,
                            ]) aria-hidden="true">@if ($step['done'])<x-owner.icon name="check" class="h-4 w-4" />@else{{ $index + 1 }}@endif</span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold {{ $step['done'] ? 'text-bq-text-muted line-through' : 'text-bq-ink' }}">
                                    {{ $step['label'] }}<span class="sr-only">{{ $step['done'] ? ' (selesai)' : ' (belum)' }}</span>
                                </p>
                                <p class="mt-0.5 text-sm text-bq-text-muted">{{ $step['hint'] }}</p>
                            </div>
                            @unless ($step['done'])
                                @php $stepButton = $index === $nextStep ? 'bq-btn bq-btn-primary bq-btn-sm' : $secondaryButton; @endphp
                                @if ($step['event'])
                                    <button type="button" x-data x-on:click="$dispatch(@js($step['event']))" class="{{ $stepButton }} shrink-0">Mulai</button>
                                @elseif ($step['url'])
                                    <a href="{{ $step['url'] }}" class="{{ $stepButton }} shrink-0">Mulai</a>
                                @endif
                            @endunless
                        </li>
                    @endforeach
                </ol>
            </section>
        @else
            {{-- Sections 2–4, refreshed while visible (AC-BRD-06) --}}
            {{-- 3. Perlu tindakan (AC-BRD-04) --}}
            <section aria-labelledby="actions-title" class="{{ $card }} min-w-0 lg:col-start-2 lg:row-start-1">
                <div class="flex items-center justify-between gap-3 border-b border-bq-border px-4 py-3 sm:px-5">
                    <h2 id="actions-title" class="text-base font-semibold text-bq-ink">Perlu Anda</h2>
                    <span x-show="live.actions.length > 0" class="sr-only" x-text="live.actions.reduce((sum, item) => sum + Number(item.count || 0), 0)"></span>
                </div>
                <div x-show="live.actions.length === 0" class="flex items-center gap-3 px-4 py-6 sm:px-5">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-bq-success-bg text-bq-success"><x-owner.icon name="check" class="h-5 w-5" /></span>
                    <p class="text-sm text-bq-text-muted">Tidak ada yang perlu ditindak. Semua beres.</p>
                </div>
                <ul x-show="live.actions.length > 0" class="divide-y divide-bq-border">
                    <template x-for="item in live.actions" x-bind:key="item.key">
                        <li>
                            <a x-bind:href="item.url" class="flex items-center justify-between gap-3 px-4 py-3 text-sm text-bq-ink transition-colors hover:bg-bq-primary-light/60 sm:px-5">
                                <span class="bq-count" x-text="item.count"></span>
                                <span class="min-w-0 flex-1" x-text="item.label"></span>
                                <x-owner.icon name="chevron-right" class="h-4 w-4" />
                            </a>
                        </li>
                    </template>
                </ul>
            </section>
            <x-ui.panel aria-label="Hari ini" class="lg:col-start-1 lg:row-start-1 lg:self-stretch">
                <div class="bq-figures">
                    <div class="bq-figures-main min-w-0">
                        <b x-text="live.figures[0]?.value"></b>
                        <span x-text="live.figures[0]?.label"></span>
                        <small x-text="live.tomorrow.count
                            ? 'Besok ' + live.tomorrow.count + ' booking, pertama ' + live.tomorrow.first_time
                            : 'Besok belum ada booking'"></small>
                    </div>
                    <div class="bq-figures-more">
                        <template x-for="figure in live.figures.slice(1)" :key="figure.key">
                            <div>
                                <b x-text="figure.value"></b>
                                <span x-text="figure.label"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </x-ui.panel>
            {{-- 4. Today's schedule on the day rail, and tomorrow --}}
            <section aria-labelledby="today-title" class="{{ $card }} min-w-0 lg:col-start-1"
                x-data="ownerDayRail">
                <div class="flex items-center justify-between gap-3 border-b border-bq-border px-4 py-3 sm:px-5">
                    <div>
                        <h2 id="today-title" class="text-bq-section text-bq-ink">Jadwal hari ini</h2>
                        <p class="text-bq-small text-bq-text-muted">{{ $todayLabel }}</p>
                    </div>
                    <x-ui.button :href="route('owner.calendar')" size="sm" variant="ghost">Buka kalender</x-ui.button>
                </div>

                <div x-show="railRows.length === 0" class="px-4 py-10 text-center">
                    <span class="mx-auto mb-2 flex h-11 w-11 items-center justify-center rounded-full bg-bq-primary-light text-bq-primary"><x-owner.icon name="calendar" /></span>
                    <p class="text-base font-semibold text-bq-ink">Belum ada booking hari ini</p>
                    <p x-show="live.next" class="mt-1 text-sm text-bq-text-muted">
                        Booking berikutnya: <span x-text="live.next ? live.next.date + ', ' + live.next.time + ' · ' + live.next.customer : ''"></span>
                    </p>
                </div>

                <x-ui.day-rail x-show="railRows.length > 0" label="Booking hari ini menurut jam">
                    <template x-for="(row, index) in railRows" x-bind:key="(row.empty ? 'slot-' : 'booking-') + row.id">
                        <li>
                            <div x-show="index === nowIndex" class="relative my-1 flex items-center gap-2" aria-hidden="true">
                                <span class="bq-num w-16 shrink-0 rounded-full bg-bq-accent px-2 py-0.5 text-center text-[11px] font-bold text-bq-ink" x-text="now.replace(':', '.')"></span>
                                <span class="h-0.5 flex-1 rounded-full bg-bq-accent"></span>
                            </div>
                            <div x-show="row.empty" class="flex flex-wrap items-center justify-between gap-2 border-b border-bq-border py-2">
                                <div class="min-w-0">
                                    <p class="font-semibold">Kosong</p>
                                    <p class="text-bq-small text-bq-text-muted" x-text="row.label"></p>
                                </div>
                                <x-ui.button size="sm" variant="ghost" icon="plus" x-on:click="$dispatch('open-dialog', { id: 'owner-walkin-dialog', slot: row, trigger: $el })">Catat booking</x-ui.button>
                            </div>
                            <template x-if="!row.empty">
                            <div class="grid grid-cols-[4rem_minmax(0,1fr)] gap-3 py-2">
                                <p class="bq-num pt-3 font-bq-display text-bq-clock font-semibold text-bq-ink" x-text="start(row).replace(':', '.')"></p>
                                <div class="relative border-l-2 pl-4" x-bind:class="row.in_progress ? 'border-bq-primary' : 'border-bq-border'">
                                    <span class="absolute top-4 -left-[5px] h-2 w-2 rounded-full" x-bind:class="row.in_progress ? 'bg-bq-primary' : 'bg-bq-border-strong'" aria-hidden="true"></span>
                                    <div class="flex flex-col gap-3 rounded-lg border p-3 sm:flex-row sm:items-start" x-bind:class="row.in_progress ? 'border-bq-primary-border bg-bq-primary-light' : 'border-bq-border bg-bq-surface'">
                                        <div class="min-w-0 flex-1">
                                            <p class="break-words text-sm font-semibold text-bq-ink" x-text="row.customer"></p>
                                            <p class="truncate text-sm text-bq-text-muted" x-bind:title="row.time + ' · ' + row.service"><span class="bq-num" x-text="row.time"></span> · <span x-text="row.service"></span></p>
                                            <p x-show="row.in_progress" class="mt-0.5 text-xs font-semibold text-bq-primary">Sedang berlangsung</p>
                                            <span x-show="!row.in_progress" class="mt-1 inline-flex items-center gap-2 text-bq-small text-bq-text-muted">
                                                <span aria-hidden="true" class="size-2 rounded-full" :class="row.status.tone === 'success' ? 'bg-bq-success' : (row.status.tone === 'warning' ? 'bg-bq-warning-dot' : 'bg-bq-primary')"></span>
                                                <span x-text="row.status.label"></span>
                                            </span>
                                        </div>
                                        <div class="flex shrink-0 flex-wrap items-center gap-2">
                                            <a x-bind:href="row.href" x-on:click="if (row.panel_url) { $event.preventDefault(); $dispatch('open-booking-panel', { url: row.panel_url, trigger: $el }); }" class="bq-btn bq-btn-ghost bq-btn-sm">Buka</a>
                                            <template x-for="outcome in [
                                                { url: row.complete_url, status: 'completed', label: 'Tandai selesai', message: 'Pastikan sesi pelanggan sudah selesai.' },
                                                { url: row.no_show_url, status: 'no_show', label: 'Tidak datang', message: 'Pastikan pelanggan tidak datang pada sesi ini.' }
                                            ].filter(outcome => outcome.url)" :key="outcome.status">
                                                <form method="POST" :action="outcome.url" :id="'dashboard-' + row.id + '-' + outcome.status">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" :value="outcome.status">
                                                    <button type="button" class="{{ $secondaryButton }}" x-text="outcome.label"
                                                        @click="$dispatch('open-confirm', { title: outcome.label + '?', message: outcome.message, formId: $el.form.id, confirmText: outcome.label })"></button>
                                                </form>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            </template>
                        </li>
                    </template>
                    <li x-show="nowIndex === railRows.length" class="my-1 flex items-center gap-2" aria-hidden="true">
                        <span class="bq-num w-16 shrink-0 rounded-full bg-bq-accent px-2 py-0.5 text-center text-[11px] font-bold text-bq-ink" x-text="now.replace(':', '.')"></span>
                        <span class="h-0.5 flex-1 rounded-full bg-bq-accent"></span>
                    </li>
                </x-ui.day-rail>

                <p class="border-t border-bq-border px-4 py-3 text-sm text-bq-text-muted sm:px-5 lg:hidden">
                    <span class="font-semibold text-bq-ink">Besok:</span>
                    <span x-text="live.tomorrow.count === 0 ? 'belum ada booking' : live.tomorrow.count + ' booking, pertama ' + live.tomorrow.first_time + ' · ' + live.tomorrow.first_customer"></span>
                </p>
            </section>

        @endif

        <div @class(['grid min-w-0 gap-5', 'lg:col-span-2 lg:grid-cols-3' => $guide, 'lg:col-start-2' => ! $guide])>
            @unless ($guide)
                <section aria-labelledby="tomorrow-title" class="{{ $card }} hidden min-w-0 self-start lg:block">
                    <div class="border-b border-bq-border px-4 py-3">
                        <h2 id="tomorrow-title" class="text-bq-section">Besok</h2>
                        <p class="text-bq-small text-bq-text-muted" x-text="live.tomorrow.date"></p>
                    </div>
                    <p x-show="live.tomorrow.count === 0" class="p-4 text-bq-small text-bq-text-muted">Belum ada booking.</p>
                    <ul class="divide-y divide-bq-border">
                        <template x-for="item in live.tomorrow.bookings" :key="item.url">
                            <li>
                                <button type="button" class="flex w-full items-center gap-3 px-4 py-3 text-left hover:bg-bq-background"
                                    x-on:click="$dispatch('open-booking-panel', { url: item.url, trigger: $el })">
                                    <span class="bq-num font-bq-display text-bq-clock font-semibold" x-text="item.time"></span>
                                    <span class="min-w-0 flex-1 truncate" x-text="item.customer" :title="item.customer"></span>
                                    <span class="shrink-0 text-bq-small text-bq-text-muted" x-text="item.status"></span>
                                </button>
                            </li>
                        </template>
                    </ul>
                </section>
            @endunless

            {{-- 5. Booking link card --}}
            @if ($link)
                <x-ui.panel title="Link booking" :class="$guide ? 'lg:col-span-2' : null">
                    <div class="grid gap-3" x-data="ownerBookingLink(@js($link), @js(route('owner.dashboard.link-shared')))">
                        <div class="flex flex-wrap items-center justify-between gap-2 rounded-bq-control border border-bq-border bg-bq-background px-3 py-2">
                            <p class="min-w-0 flex-1 break-all text-bq-body">{{ $link['url'] }}</p>
                            <x-ui.button size="sm" x-on:click="copy()" x-text="copied ? 'Tersalin' : 'Salin link'">Salin link</x-ui.button>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <x-ui.button size="sm" x-show="canShare" x-cloak x-on:click="share()">Bagikan</x-ui.button>
                            <x-ui.button :href="'https://wa.me/?text='.rawurlencode($link['share_text'])" target="_blank" rel="noopener" size="sm" icon="whatsapp" x-on:click="mark()">WhatsApp</x-ui.button>
                            @if ($link['qr_url'])
                                <x-ui.button :href="$link['qr_url']" size="sm" icon="qr">Kode QR</x-ui.button>
                            @endif
                        </div>
                    </div>
                </x-ui.panel>
            @endif

            {{-- 6. Quick actions --}}
            @if (count($quickActions) > 0)
                <section aria-labelledby="quick-title" class="{{ $card }} p-4 sm:p-5">
                    <h2 id="quick-title" class="text-base font-semibold text-bq-ink">Aksi cepat</h2>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($quickActions as $action)
                            @if ($action['event'] !== '')
                                <button type="button" data-quick-action="{{ $action['key'] }}" x-data x-on:click="$dispatch('open-dialog', { id: @js($action['event']), trigger: $el })" class="bq-btn bq-btn-secondary bq-btn-sm justify-start">
                                    <x-owner.icon name="plus" class="h-4 w-4 text-bq-primary" />{{ $action['label'] }}
                                </button>
                            @else
                                <a href="{{ $action['url'] }}" data-quick-action="{{ $action['key'] }}" class="bq-btn bq-btn-secondary bq-btn-sm justify-start">
                                    <x-owner.icon name="plus" class="h-4 w-4 text-bq-primary" />{{ $action['label'] }}
                                </a>
                            @endif
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>
</div>

@if ($profileIncomplete || $errors->any() && old('_form') === 'complete-profile')
    @include('components.owner.modal-complete-profile', ['showProfilePrompt' => false])
@endif
@endsection
