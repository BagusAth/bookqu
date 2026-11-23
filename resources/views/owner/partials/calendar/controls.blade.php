{{-- BQ-162: calendar filter in the side panel (prototype filter-kalender). --}}
<form method="GET" action="{{ route('owner.calendar') }}" class="grid gap-4">
    <input type="hidden" name="view" value="{{ $view }}">
    <input type="hidden" name="date" value="{{ $date->toDateString() }}">
    @if ($unitMode)
        <input type="hidden" name="kolom" value="unit">
    @endif
    <div class="grid gap-1.5">
        <label for="calendar-filter-service" class="bq-label">Layanan</label>
        <select id="calendar-filter-service" name="service_id" class="bq-input w-full">
            <option value="">Semua layanan</option>
            @foreach ($services as $service)
                <option value="{{ $service->id }}" @selected(($filters['service_id'] ?? null) === $service->id)>{{ $service->namalayanan }}</option>
            @endforeach
        </select>
    </div>
    @if ($units->isNotEmpty())
        <div class="grid gap-1.5">
            <label for="calendar-filter-unit" class="bq-label">Unit</label>
            <select id="calendar-filter-unit" name="unit_id" class="bq-input w-full">
                <option value="">Semua unit</option>
                @foreach ($units as $unit)
                    <option value="{{ $unit->id }}" @selected(($filters['unit_id'] ?? null) === $unit->id)>{{ $unit->name }}</option>
                @endforeach
            </select>
        </div>
    @endif
    <div class="grid gap-1.5">
        <label for="calendar-filter-status" class="bq-label">Status slot</label>
        <select id="calendar-filter-status" name="status" class="bq-input w-full">
            <option value="">Semua status</option>
            @foreach ($statuses as $value => $statusLabel)
                <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $statusLabel }}</option>
            @endforeach
        </select>
    </div>
    @if ($scheduleUrl)
        <p class="md:hidden"><a class="bq-link inline-flex items-center gap-1" href="{{ $scheduleUrl }}"><x-owner.icon name="clock" class="h-4 w-4" />Atur jam buka</a></p>
    @endif
    <div class="flex justify-end gap-2">
        <a class="bq-btn bq-btn-secondary" href="{{ route('owner.calendar', ['view' => $view, 'date' => $date->toDateString()] + ($unitMode ? ['kolom' => 'unit'] : [])) }}">Hapus filter</a>
        <x-ui.button type="submit" variant="primary">Terapkan</x-ui.button>
    </div>
</form>
