@extends('layouts.owner-layout')

@section('title', 'Kalender')

@section('content')
@php
    // BQ-161 (audit K-03): each state has a fill and a strong left edge, so states differ by more than text.
    $slotColors = [
        'Tersedia' => 'border-l-4 border-l-bq-success bg-bq-success-bg/70',
        'Terisi' => 'border-l-4 border-l-bq-primary bg-bq-primary-light',
        'Ditutup' => 'border-l-4 border-l-bq-warning bg-bq-warning-bg/70',
        'Terlewat' => 'border-l-4 border-l-bq-border-strong bg-bq-surface-2 text-bq-text-muted',
        'Tidak tersedia' => 'border-l-4 border-l-bq-danger bg-bq-danger-bg/70',
    ];
    $legendDots = ['Tersedia' => 'bg-bq-success', 'Terisi' => 'bg-bq-primary', 'Ditutup' => 'bg-bq-warning', 'Terlewat' => 'bg-bq-border-strong', 'Tidak tersedia' => 'bg-bq-danger'];
@endphp
<div class="mx-auto max-w-7xl space-y-5" x-data="{
    slot: null,
    calendarTrigger: null,
    busy: false,
    feedback: '',
    slotErrors: {},
    async saveSlot(form, submitter) {
        if (this.busy || !this.slot) return;
        const currentSlot = this.slot.id;
        const fields = new FormData(form, submitter);
        this.busy = true;
        this.slotErrors = {};
        try {
            const response = await fetch(form.action, {
                method: 'POST', body: fields, headers: { Accept: 'application/json' },
            });
            const payload = await response.json();
            if (!response.ok) {
                this.slotErrors = payload.errors || { slot: ['Perubahan slot tidak dapat disimpan.'] };
                return;
            }
            const refreshed = await fetch(window.location.href);
            if (!refreshed.ok) throw new Error();
            const page = new DOMParser().parseFromString(await refreshed.text(), 'text/html');
            const results = page.querySelector('[data-calendar-results]');
            if (!results) throw new Error();
            const updated = results.querySelector('[data-calendar-slot-id=&quot;' + currentSlot + '&quot;]');
            this.$refs.results.replaceWith(document.importNode(results, true));
            if (this.slot?.id === currentSlot) this.slot = updated ? JSON.parse(updated.dataset.calendarSlot) : null;
            $dispatch('toast', { type: 'success', message: payload.message });
        } catch {
            this.slotErrors = { slot: ['Status slot belum dapat diperiksa. Muat ulang Kalender.'] };
        } finally {
            this.busy = false;
        }
    },

}">
    <x-owner.page-header title="Kalender" purpose="Kapan ramai, kapan kosong, dan siapa yang datang.">
        @if ($scheduleUrl)
            <x-slot:action><a href="{{ $scheduleUrl }}" class="bq-btn bq-btn-secondary"><x-owner.icon name="clock" class="h-4 w-4" />Atur jam buka</a></x-slot:action>
        @endif
    </x-owner.page-header>
    <x-owner.tabs :tabs="$views" :active="$view" param="view" label="Tampilan kalender" />

    <nav class="flex flex-wrap items-center justify-between gap-3" aria-label="Navigasi tanggal">
        <div class="flex items-center gap-1 bq-panel p-1">
            <a class="bq-btn bq-btn-ghost bq-btn-icon bq-btn-sm" href="{{ route('owner.calendar', ['view' => $view, 'date' => $previous->toDateString()] + $filters) }}" aria-label="Sebelumnya" title="Sebelumnya"><x-owner.icon name="chevron-left" class="h-5 w-5" /><span class="sr-only">Sebelumnya</span></a>
            <a class="bq-btn bq-btn-ghost bq-btn-sm" href="{{ route('owner.calendar', ['view' => $view, 'date' => $today] + $filters) }}">Hari ini</a>
            <a class="bq-btn bq-btn-ghost bq-btn-icon bq-btn-sm" href="{{ route('owner.calendar', ['view' => $view, 'date' => $next->toDateString()] + $filters) }}" aria-label="Berikutnya" title="Berikutnya"><x-owner.icon name="chevron-right" class="h-5 w-5" /><span class="sr-only">Berikutnya</span></a>
        </div>
        <p class="text-lg font-bold text-bq-ink"><span class="sr-only">{{ $view === 'day' ? 'Jadwal Harian Â· ' : '' }}</span>{{ $dateLabel }}</p>
    </nav>

    @if ($view === 'day' && $canViewUnits)
        @php $dayParameters = ['view' => 'day', 'date' => $date->toDateString()] + array_diff_key($filters, ['kolom' => true]); @endphp
        <div class="hidden md:block">
        <x-ui.segmented label="Kolom kalender" :selected="$unitMode ? 'unit' : 'service'" :options="[
            'service' => ['label' => 'Per layanan', 'href' => route('owner.calendar', $dayParameters)],
            'unit' => ['label' => 'Per unit', 'href' => route('owner.calendar', $dayParameters + ['kolom' => 'unit'])],
        ]" />
        </div>
    @endif

    @include('owner.partials.calendar.controls')
    <ul class="flex flex-wrap gap-x-4 gap-y-1.5 text-xs text-bq-text-muted" aria-label="Keterangan status slot">
        @foreach ($legendDots as $label => $dot)
            <li class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full {{ $dot }}" aria-hidden="true"></span>{{ $label }}</li>
        @endforeach
    </ul>
    <p x-cloak x-show="feedback" x-text="feedback" role="status" class="text-sm"></p>

    <div x-ref="results" data-calendar-results>
        <x-owner.walkin-success />
        @if ($errors->any())
            <p data-calendar-error class="text-sm text-bq-danger" role="alert">{{ $errors->first() }}</p>
        @endif
        @if (! $hasEntries)
            <x-owner.empty-state title="Belum ada slot di periode ini" :action-label="$scheduleUrl ? 'Jam Buka & Harga' : null" :action-href="$scheduleUrl" />
        @elseif ($view === 'day')
            @include('owner.partials.calendar.day-view', ['day' => reset($days)])
        @else
            @include('owner.partials.calendar.range-view')
        @endif
    </div>

    @include('owner.partials.calendar.detail-modal')
</div>
@endsection
