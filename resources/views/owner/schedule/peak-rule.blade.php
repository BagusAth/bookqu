{{-- BQ-162: peak-hour rule form in the side panel (prototype form-jamramai). --}}
<form method="POST" class="grid gap-4"
    action="{{ $rule ? route('owner.schedule.peak-rules.update', $rule) : route('owner.schedule.peak-rules.store') }}">
    @csrf
    @if ($rule)
        @method('PUT')
    @endif
    <div class="grid gap-1.5">
        <label for="peak-name-{{ $rule?->id ?? 'new' }}" class="bq-label">Nama aturan <span class="text-bq-danger" aria-hidden="true">*</span></label>
        <input id="peak-name-{{ $rule?->id ?? 'new' }}" name="name" value="{{ $rule?->name }}" required maxlength="100" placeholder="Misalnya: Malam kerja" class="bq-input w-full">
    </div>
    <fieldset class="grid gap-1.5">
        <legend class="bq-label mb-1">Hari</legend>
        <div class="flex flex-wrap gap-x-3 gap-y-1">
            @foreach ([1 => 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $day => $label)
                <x-ui.checkbox name="days[]" value="{{ $day }}" :label="$label" :checked="in_array($day, $rule?->days ?? [], true)" />
            @endforeach
        </div>
    </fieldset>
    <div class="grid grid-cols-2 gap-3">
        <div class="grid gap-1.5">
            <label for="peak-start-{{ $rule?->id ?? 'new' }}" class="bq-label">Dari jam</label>
            <input id="peak-start-{{ $rule?->id ?? 'new' }}" type="time" name="starts_at" value="{{ substr($rule?->starts_at ?? '', 0, 5) }}" required class="bq-input w-full">
        </div>
        <div class="grid gap-1.5">
            <label for="peak-end-{{ $rule?->id ?? 'new' }}" class="bq-label">Sampai jam</label>
            <input id="peak-end-{{ $rule?->id ?? 'new' }}" type="time" name="ends_at" value="{{ substr($rule?->ends_at ?? '', 0, 5) }}" required class="bq-input w-full">
        </div>
    </div>
    <div class="grid gap-1.5">
        <label for="peak-extra-{{ $rule?->id ?? 'new' }}" class="bq-label">Tambahan harga</label>
        <div class="flex min-h-bq-control items-center rounded-bq-control border border-bq-border-strong bg-bq-surface focus-within:border-bq-primary focus-within:ring-2 focus-within:ring-bq-primary/20">
            <span class="pl-3 text-bq-form text-bq-text-muted">Rp</span>
            <input id="peak-extra-{{ $rule?->id ?? 'new' }}" type="number" name="extra_amount" value="{{ $rule ? (int) $rule->extra_amount : '' }}" required min="0" max="100000000" step="1" inputmode="numeric"
                class="bq-num min-w-0 flex-1 border-0 bg-transparent px-3 text-bq-form outline-none focus:ring-0">
        </div>
        <p class="bq-hint">Ditambahkan ke harga slot yang mulai pada jam ini.</p>
    </div>
    <fieldset class="grid gap-1.5">
        <legend class="bq-label mb-1">Berlaku untuk</legend>
        <p class="bq-hint">Kosongkan untuk semua layanan.</p>
        @foreach ($daftarlayanan as $service)
            <x-ui.checkbox name="service_ids[]" value="{{ $service->id }}" :label="$service->namalayanan" :checked="(bool) $rule?->services->contains($service->id)" />
        @endforeach
    </fieldset>
    <div class="flex justify-end"><x-ui.button type="submit" variant="primary">{{ $rule ? 'Simpan perubahan' : 'Simpan aturan' }}</x-ui.button></div>
</form>
