@extends('layouts.owner-layout')

@section('title', 'Kalender')

@section('content')
@php
    $slotColors = [
        'Tersedia' => 'bg-bq-success-bg',
        'Terisi' => 'bg-bq-info-bg',
        'Ditutup' => 'bg-bq-warning-bg',
        'Terlewat' => 'bg-bq-background',
        'Tidak tersedia' => 'bg-bq-danger-bg',
    ];
@endphp
<div class="mx-auto max-w-7xl space-y-5" x-data="{
    slot: null,
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
    async saveWalkIn(form) {
        if (this.busy) return;
        this.busy = true;
        this.feedback = '';
        try {
            const response = await fetch(form.action, { method: 'POST', body: new FormData(form) });
            if (!response.ok || new URL(response.url).origin !== window.location.origin) throw new Error();
            const page = new DOMParser().parseFromString(await response.text(), 'text/html');
            const results = page.querySelector('[data-calendar-results]');
            if (!results) throw new Error();
            const error = results.querySelector('[data-calendar-error]');
            if (error) {
                this.feedback = error.textContent.trim();
                return;
            }
            this.$dispatch('close-dialog', { id: 'calendar-slot' });
            this.$refs.results.replaceWith(document.importNode(results, true));
            this.slot = null;
            this.feedback = 'Walk-in berhasil dicatat.';
        } catch {
            this.feedback = 'Status penyimpanan belum dapat diperiksa. Muat ulang Kalender sebelum mencoba lagi.';
        } finally {
            this.busy = false;
        }
    }
}">
    <x-owner.page-header title="Kalender" purpose="Lihat jadwal layanan dan kelola slot.">
        @if ($scheduleUrl)
            <x-slot:action><a href="{{ $scheduleUrl }}" class="text-sm font-semibold text-bq-primary underline">Atur jam buka</a></x-slot:action>
        @endif
    </x-owner.page-header>
    <x-owner.tabs :tabs="$views" :active="$view" param="view" label="Tampilan kalender" />

    <nav class="flex flex-wrap items-center gap-3 text-sm" aria-label="Navigasi tanggal">
        <a class="text-bq-primary underline" href="{{ route('owner.calendar', ['view' => $view, 'date' => $previous->toDateString()] + $filters) }}">Sebelumnya</a>
        <span class="font-semibold">{{ $view === 'day' ? 'Jadwal Harian · ' : '' }}{{ $dateLabel }}</span>
        <a class="text-bq-primary underline" href="{{ route('owner.calendar', ['view' => $view, 'date' => $next->toDateString()] + $filters) }}">Berikutnya</a>
        <a class="rounded-lg border border-bq-border px-3 py-1" href="{{ route('owner.calendar', ['view' => $view, 'date' => $today] + $filters) }}">Hari ini</a>
    </nav>

    @include('owner.partials.calendar.controls')
    <ul class="flex flex-wrap gap-2 text-xs" aria-label="Keterangan status slot">
        @foreach ($slotColors as $label => $background)
            <li class="rounded-md border border-bq-border px-2 py-1 {{ $background }}">{{ $label }}</li>
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
