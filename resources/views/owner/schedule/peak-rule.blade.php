<form method="POST" class="mt-3 space-y-3"
    action="{{ $rule ? route('owner.schedule.peak-rules.update', $rule) : route('owner.schedule.peak-rules.store') }}">
    @csrf
    @if ($rule)
        @method('PUT')
    @endif
    <div class="grid gap-3 sm:grid-cols-4">
        <label class="grid gap-1 text-sm">Nama aturan
        <input name="name" value="{{ $rule?->name }}" required maxlength="100" class="rounded border border-bq-border bg-bq-surface p-2">
    </label>
        <label class="grid gap-1 text-sm">Mulai
        <input type="time" name="starts_at" value="{{ substr($rule?->starts_at ?? '', 0, 5) }}" required class="rounded border border-bq-border bg-bq-surface p-2">
    </label>
        <label class="grid gap-1 text-sm">Selesai
        <input type="time" name="ends_at" value="{{ substr($rule?->ends_at ?? '', 0, 5) }}" required class="rounded border border-bq-border bg-bq-surface p-2">
    </label>
        <label class="grid gap-1 text-sm">Tambahan (Rp)
        <input type="number" name="extra_amount" value="{{ $rule?->extra_amount }}" required min="0" max="100000000" step="0.01" class="rounded border border-bq-border bg-bq-surface p-2">
    </label>
    </div>
    <fieldset class="flex flex-wrap gap-3 text-sm">
        <legend class="mb-1">Hari berlaku</legend>
        @foreach ($weekdays as $day => $label)
            <label><input type="checkbox" name="days[]" value="{{ $day }}" @checked(in_array($day, $rule?->days ?? [], true))> {{ $label }}</label>
        @endforeach
    </fieldset>
    <fieldset class="flex flex-wrap gap-3 text-sm">
        <legend class="mb-1">Layanan (kosong berarti semua)</legend>
        @foreach ($daftarlayanan as $service)
            <label><input type="checkbox" name="service_ids[]" value="{{ $service->id }}" @checked($rule?->services->contains($service->id))> {{ $service->namalayanan }}</label>
        @endforeach
    </fieldset>
    <button class="rounded-lg bg-bq-primary px-4 py-2 text-sm font-semibold text-white">Simpan aturan</button>
</form>
