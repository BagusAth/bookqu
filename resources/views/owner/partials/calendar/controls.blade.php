<form method="GET" action="{{ route('owner.calendar') }}" class="flex flex-wrap items-end gap-3">
    <input type="hidden" name="view" value="{{ $view }}">
    <input type="hidden" name="date" value="{{ $date->toDateString() }}">
    <label class="grid gap-1 text-sm">Layanan
        <select name="service_id" @change="$el.form.requestSubmit()" class="rounded-lg border border-bq-border bg-bq-surface p-2">
            <option value="">Semua layanan</option>
            @foreach ($services as $service)
                <option value="{{ $service->id }}" @selected(($filters['service_id'] ?? null) === $service->id)>{{ $service->namalayanan }}</option>
            @endforeach
        </select>
    </label>
    @if ($units->isNotEmpty())
        <label class="grid gap-1 text-sm">Unit
            <select name="unit_id" @change="$el.form.requestSubmit()" class="rounded-lg border border-bq-border bg-bq-surface p-2">
                <option value="">Semua unit</option>
                @foreach ($units as $unit)
                    <option value="{{ $unit->id }}" @selected(($filters['unit_id'] ?? null) === $unit->id)>{{ $unit->name }}</option>
                @endforeach
            </select>
        </label>
    @endif
    <label class="grid gap-1 text-sm">Status
        <select name="status" @change="$el.form.requestSubmit()" class="rounded-lg border border-bq-border bg-bq-surface p-2">
            <option value="">Semua status</option>
            @foreach ($statuses as $value => $statusLabel)
                <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $statusLabel }}</option>
            @endforeach
        </select>
    </label>
    <noscript><button class="rounded-lg border border-bq-border px-3 py-2 text-sm font-semibold">Terapkan</button></noscript>
</form>
