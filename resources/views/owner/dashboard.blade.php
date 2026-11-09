@extends('layouts.owner-layout')

@section('title', 'Beranda')

{{--
    Beranda (BQ-136, owner-portal.md §8.1): one banner, today in four figures, "Perlu tindakan",
    today's schedule with tomorrow's summary, the booking link card, quick actions, and the new
    owner guide in place of sections 2–4 until it is done. Data from App\Actions\Dashboard\GetBeranda;
    sections 2–4 refresh while the page is visible and render as text only. No charts (AC-BRD-07).
--}}

@php
    $bannerTone = [
        'danger' => 'border-bq-danger/30 bg-bq-danger-bg text-bq-danger',
        'warning' => 'border-bq-warning/30 bg-bq-warning-bg text-bq-warning',
        'info' => 'border-bq-info/30 bg-bq-info-bg text-bq-info',
    ];
    $badgeTone = "{ 'bg-bq-info-bg text-bq-info': badge.tone === 'info', 'bg-bq-success-bg text-bq-success': badge.tone === 'success', 'bg-bq-warning-bg text-bq-warning': badge.tone === 'warning', 'bg-bq-danger-bg text-bq-danger': badge.tone === 'danger', 'bg-bq-background text-bq-text-muted': !['info','success','warning','danger'].includes(badge.tone) }";
    $card = 'rounded-xl border border-bq-border bg-bq-surface';
    $secondaryButton = 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-bq-border bg-bq-surface px-3 py-1.5 text-sm font-medium text-bq-text hover:bg-bq-background';
    $greetingName = \Illuminate\Support\Str::of((string) (auth()->user()?->namalengkap ?? ''))->before(' ')->value();
@endphp

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <x-owner.page-header :title="$greetingName !== '' ? 'Halo, '.$greetingName : 'Beranda'" purpose="Yang perlu Anda ketahui dan lakukan hari ini." />

    {{-- 1. One status banner (AC-BRD-01) --}}
    @if ($banner)
        <div role="status" data-beranda-banner="{{ $banner['kind'] }}" class="flex flex-col gap-3 rounded-xl border px-4 py-3 sm:flex-row sm:items-center sm:justify-between {{ $bannerTone[$banner['tone']] ?? $bannerTone['info'] }}">
            <div class="text-sm">
                <p class="font-medium">{{ $banner['message'] }}</p>
                @if ($banner['note'])
                    <p class="mt-0.5">{{ $banner['note'] }}</p>
                @endif
            </div>
            @if ($banner['action'])
                <a href="{{ $banner['action']['url'] }}" class="inline-flex shrink-0 items-center justify-center rounded-lg bg-bq-surface px-3 py-1.5 text-sm font-semibold text-bq-text ring-1 ring-inset ring-current/20 hover:bg-bq-background">{{ $banner['action']['label'] }}</a>
            @endif
        </div>
    @endif

    @if ($guide)
        {{-- 7. New owner guide (AC-BRD-05), shown instead of sections 2–4 --}}
        <section data-owner-guide aria-labelledby="owner-guide-title" class="{{ $card }} p-5">
            <div class="flex items-baseline justify-between gap-3">
                <h2 id="owner-guide-title" class="text-base font-semibold text-bq-text">Siapkan bisnis Anda</h2>
                <p class="text-sm tabular-nums text-bq-text-muted">{{ $guide['done'] }} dari 4 langkah</p>
            </div>
            <ol class="mt-4 space-y-3">
                @foreach ($guide['steps'] as $index => $step)
                    <li class="flex items-start gap-3">
                        <span @class([
                            'flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-sm font-semibold',
                            'bg-bq-success-bg text-bq-success' => $step['done'],
                            'bg-bq-background text-bq-text-muted ring-1 ring-inset ring-bq-border' => ! $step['done'],
                        ]) aria-hidden="true">{{ $step['done'] ? '✓' : $index + 1 }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium {{ $step['done'] ? 'text-bq-text-muted line-through' : 'text-bq-text' }}">
                                {{ $step['label'] }}<span class="sr-only">{{ $step['done'] ? ' (selesai)' : ' (belum)' }}</span>
                            </p>
                            <p class="text-xs text-bq-text-muted">{{ $step['hint'] }}</p>
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
        <div x-data="ownerBeranda(@js($live), @js(route('owner.dashboard.polling')))" class="space-y-6">
            {{-- 2. Today in four figures --}}
            <section aria-label="Hari ini" class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <template x-for="figure in live.figures" x-bind:key="figure.key">
                    <div class="{{ $card }} p-4">
                        <p class="text-sm text-bq-text-muted" x-text="figure.label"></p>
                        <p class="mt-1 break-words text-xl font-semibold tabular-nums text-bq-text sm:text-2xl" x-text="figure.value"></p>
                        <p class="mt-0.5 text-xs text-bq-text-muted" x-text="figure.hint"></p>
                    </div>
                </template>
            </section>

            <div class="grid gap-6 lg:grid-cols-3">
                {{-- 4. Today's schedule and tomorrow --}}
                <section aria-labelledby="today-title" class="{{ $card }} lg:col-span-2">
                    <h2 id="today-title" class="border-b border-bq-border px-4 py-3 text-base font-semibold text-bq-text">Jadwal hari ini</h2>

                    <div x-show="live.today.length === 0" class="px-4 py-8 text-center">
                        <p class="text-sm font-medium text-bq-text">Belum ada booking hari ini</p>
                        <p x-show="live.next" class="mt-1 text-sm text-bq-text-muted">
                            Booking berikutnya: <span x-text="live.next ? live.next.date + ', ' + live.next.time + ' · ' + live.next.customer : ''"></span>
                        </p>
                    </div>

                    <ul x-show="live.today.length > 0" class="divide-y divide-bq-border">
                        <template x-for="row in live.today" x-bind:key="row.id">
                            <li class="flex items-start gap-3 px-4 py-3" x-bind:class="row.in_progress ? 'bg-bq-primary-light/40' : ''">
                                <div class="w-24 shrink-0">
                                    <p class="text-sm font-semibold tabular-nums text-bq-text" x-text="row.time"></p>
                                    <p x-show="row.in_progress" class="text-xs font-medium text-bq-primary">Sedang berlangsung</p>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="break-words text-sm font-medium text-bq-text" x-text="row.customer"></p>
                                    <p class="truncate text-xs text-bq-text-muted" x-text="row.service"></p>
                                    <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                        <template x-for="badge in [row.status, row.payment]">
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium" x-bind:class="{{ $badgeTone }}" x-text="badge.label"></span>
                                        </template>
                                    </div>
                                </div>
                                <div class="flex shrink-0 flex-col items-end gap-2">
                                    <a x-bind:href="row.href" x-on:click="if (row.panel_url) { $event.preventDefault(); $dispatch('open-booking-panel', { url: row.panel_url, trigger: $el }); }" class="{{ $secondaryButton }}">Buka</a>
                                    <form x-show="row.complete_url" method="POST" x-bind:action="row.complete_url">
                                        @csrf
                                        <input type="hidden" name="_method" value="PATCH">
                                        <input type="hidden" name="status" value="completed">
                                        <button type="submit" class="{{ $secondaryButton }}">Tandai selesai</button>
                                    </form>
                                </div>
                            </li>
                        </template>
                    </ul>

                    <p class="border-t border-bq-border px-4 py-3 text-sm text-bq-text-muted">
                        <span class="font-medium text-bq-text">Besok:</span>
                        <span x-text="live.tomorrow.count === 0 ? 'belum ada booking' : live.tomorrow.count + ' booking, pertama ' + live.tomorrow.first_time + ' · ' + live.tomorrow.first_customer"></span>
                    </p>
                </section>

                {{-- 3. Perlu tindakan (AC-BRD-04) --}}
                <section aria-labelledby="actions-title" class="{{ $card }}">
                    <h2 id="actions-title" class="border-b border-bq-border px-4 py-3 text-base font-semibold text-bq-text">Perlu tindakan</h2>
                    <p x-show="live.actions.length === 0" class="px-4 py-6 text-sm text-bq-text-muted">Tidak ada yang perlu ditindak. Semua beres.</p>
                    <ul x-show="live.actions.length > 0" class="divide-y divide-bq-border">
                        <template x-for="item in live.actions" x-bind:key="item.key">
                            <li>
                                <a x-bind:href="item.url" class="flex items-center justify-between gap-3 px-4 py-3 text-sm text-bq-text hover:bg-bq-background">
                                    <span x-text="item.label"></span>
                                    <span class="inline-flex min-w-6 items-center justify-center rounded-full bg-bq-warning-bg px-2 py-0.5 text-xs font-semibold tabular-nums text-bq-warning" x-text="item.count"></span>
                                </a>
                            </li>
                        </template>
                    </ul>
                </section>
            </div>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- 5. Booking link card --}}
        @if ($link)
            <section aria-labelledby="link-title" class="{{ $card }} p-4 lg:col-span-2" x-data="ownerBookingLink(@js($link), @js(route('owner.dashboard.link-shared')))">
                <h2 id="link-title" class="text-base font-semibold text-bq-text">Link booking Anda</h2>
                <p class="mt-1 text-sm text-bq-text-muted">Bagikan ke pelanggan lewat WhatsApp, Instagram atau Google Maps.</p>
                <p class="mt-3 break-all rounded-lg bg-bq-background px-3 py-2 font-mono text-sm text-bq-text">{{ $link['url'] }}</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <button type="button" x-on:click="copy()" class="inline-flex items-center rounded-lg bg-bq-primary px-3 py-1.5 text-sm font-medium text-white hover:bg-bq-primary-hover" x-text="copied ? 'Tersalin' : 'Salin link'">Salin link</button>
                    <button type="button" x-show="canShare" x-cloak x-on:click="share()" class="{{ $secondaryButton }}">Bagikan</button>
                    <a href="https://wa.me/?text={{ rawurlencode($link['share_text']) }}" target="_blank" rel="noopener" x-on:click="mark()" class="{{ $secondaryButton }}">Kirim lewat WhatsApp</a>
                </div>
            </section>
        @endif

        {{-- 6. Quick actions --}}
        @if (count($quickActions) > 0)
            <section aria-labelledby="quick-title" class="{{ $card }} p-4">
                <h2 id="quick-title" class="text-base font-semibold text-bq-text">Aksi cepat</h2>
                <div class="mt-3 grid grid-cols-2 gap-2 lg:grid-cols-1">
                    @foreach ($quickActions as $action)
                        @if ($action['event'] !== '')
                            <button type="button" data-quick-action="{{ $action['key'] }}" x-data x-on:click="$dispatch('open-dialog', { id: @js($action['event']), trigger: $el })" class="{{ $secondaryButton }} whitespace-nowrap py-2">+ {{ $action['label'] }}</button>
                        @else
                            <a href="{{ $action['url'] }}" data-quick-action="{{ $action['key'] }}" class="{{ $secondaryButton }} whitespace-nowrap py-2">+ {{ $action['label'] }}</a>
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
