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
    <x-owner.page-header title="Kalender" purpose="Kapan ramai, kapan kosong, dan siapa yang datang." />

    {{-- BQ-162: one toolbar as in the prototype (#/kalender): view, date, columns, opening hours, filter. --}}
    @php
        $activeCalendarFilters = count(array_diff_key($filters, ['kolom' => true]));
        $viewOptions = collect($views)->mapWithKeys(fn ($label, $key) => [$key => ['label' => $label, 'href' => route('owner.calendar', ['view' => $key, 'date' => $date->toDateString()] + $filters)]])->all();
    @endphp
    <h2 class="sr-only">{{ $view === 'day' ? 'Jadwal Harian · ' : '' }}{{ $dateLabel }}</h2>
    <div class="bq-toolbar" role="toolbar" aria-label="Tampilan kalender">
        <x-ui.segmented label="Tampilan" :selected="$view" :options="$viewOptions" />
        <x-ui.date-nav :label="$dateLabel"
            :previous="route('owner.calendar', ['view' => $view, 'date' => $previous->toDateString()] + $filters)"
            :today="route('owner.calendar', ['view' => $view, 'date' => $today] + $filters)"
            :next="route('owner.calendar', ['view' => $view, 'date' => $next->toDateString()] + $filters)" />
        <span class="ml-auto flex flex-wrap items-center gap-2">
            @if ($view === 'day' && $canViewUnits)
                @php $dayParameters = ['view' => 'day', 'date' => $date->toDateString()] + array_diff_key($filters, ['kolom' => true]); @endphp
                <x-ui.segmented class="max-md:hidden" label="Kolom kalender" :selected="$unitMode ? 'unit' : 'service'" :options="[
                    'service' => ['label' => 'Per layanan', 'href' => route('owner.calendar', $dayParameters)],
                    'unit' => ['label' => 'Per unit', 'href' => route('owner.calendar', $dayParameters + ['kolom' => 'unit'])],
                ]" />
            @endif
            @if ($scheduleUrl)
                <a href="{{ $scheduleUrl }}" class="bq-btn bq-btn-ghost bq-btn-sm max-md:hidden"><x-owner.icon name="clock" class="h-4 w-4" />Atur jam buka</a>
            @endif
            <x-ui.button size="sm" icon="filter" x-on:click="$dispatch('open-dialog', { id: 'calendar-filter', trigger: $el })">Filter{{ $activeCalendarFilters ? ' ('.$activeCalendarFilters.')' : '' }}</x-ui.button>
        </span>
    </div>
    <x-owner.drawer id="calendar-filter" title="Filter kalender">
        @include('owner.partials.calendar.controls')
    </x-owner.drawer>
    <p x-cloak x-show="feedback" x-text="feedback" role="status" class="text-sm"></p>

    <div x-ref="results" data-calendar-results>
        <x-owner.walkin-success />
        @if ($errors->any())
            <p data-calendar-error class="text-sm text-bq-danger" role="alert">{{ $errors->first() }}</p>
        @endif
        @if (! $hasEntries)
            <section class="bq-panel">
                <x-ui.empty title="Belum ada slot di periode ini" description="Atur jam buka, lalu slot terbuka otomatis." icon="calendar">
                    @if ($scheduleUrl)
                        <x-slot:action><x-ui.button variant="primary" :href="$scheduleUrl">Atur jam buka</x-ui.button></x-slot:action>
                    @endif
                </x-ui.empty>
            </section>
        @elseif ($view === 'day')
            @include('owner.partials.calendar.day-view', ['day' => reset($days)])
        @else
            @include('owner.partials.calendar.range-view')
        @endif
    </div>

    @include('owner.partials.calendar.detail-modal')
</div>
@endsection
