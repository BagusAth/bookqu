<form method="GET" action="{{ route('owner.calendar') }}" class="bq-panel flex flex-wrap items-end gap-3 p-3 sm:p-4">
    <input type="hidden" name="view" value="{{ $view }}">
    <input type="hidden" name="date" value="{{ $date->toDateString() }}">
    <label class="grid min-w-0 flex-1 gap-1.5 sm:max-w-64 sm:flex-none"><span class="bq-label">Layanan</span>
        <select name="service_id" @change="$el.form.requestSubmit()" class="bq-input w-full">
            <option value="">Semua layanan</option>
            @foreach ($services as $service)
                <option value="{{ $service->id }}" @selected(($filters['service_id'] ?? null) === $service->id)>{{ $service->namalayanan }}</option>
            @endforeach
        </select>
    </label>
    @if ($units->isNotEmpty())
        <label class="grid min-w-0 flex-1 gap-1.5 sm:max-w-56 sm:flex-none"><span class="bq-label">Unit</span>
            <select name="unit_id" @change="$el.form.requestSubmit()" class="bq-input w-full">
                <option value="">Semua unit</option>
                @foreach ($units as $unit)
                    <option value="{{ $unit->id }}" @selected(($filters['unit_id'] ?? null) === $unit->id)>{{ $unit->name }}</option>
                @endforeach
            </select>
        </label>
    @endif
    <label class="grid min-w-0 flex-1 gap-1.5 sm:max-w-48 sm:flex-none"><span class="bq-label">Status</span>
        <select name="status" @change="$el.form.requestSubmit()" class="bq-input w-full">
            <option value="">Semua status</option>
            @foreach ($statuses as $value => $statusLabel)
                <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $statusLabel }}</option>
            @endforeach
        </select>
    </label>
    <noscript><button class="bq-btn bq-btn-secondary">Terapkan</button></noscript>
</form>
