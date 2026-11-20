<div x-show="{{ $model }}.discount_type === 'percentage'">
    <label class="block space-y-1 text-xs font-semibold text-bq-text">
        <span>Potongan maksimum (Rp)</span>
        <input type="number" name="max_discount_amount" min="0" step="0.01"
               x-model="{{ $model }}.max_discount_amount"
               placeholder="Kosongkan jika tanpa batas"
               class="bq-input w-full">
        <x-owner.field-error name="max_discount_amount" :form="$errorForm" />
    </label>
</div>

<fieldset class="space-y-2">
    <legend class="text-xs font-semibold text-bq-text">Berlaku untuk</legend>
    <p class="text-xs text-bq-text-muted">Kosongkan untuk semua layanan.</p>
    <input type="hidden" name="applicable_services" value="">
    <div class="max-h-32 space-y-2 overflow-y-auto">
        @foreach ($services as $service)
            <label class="flex items-center gap-2 text-sm text-bq-text">
                <input type="checkbox" name="applicable_services[]" value="{{ $service->id }}"
                       x-model="{{ $model }}.applicable_services">
                <span>{{ $service->namalayanan }}</span>
            </label>
        @endforeach
    </div>
    <x-owner.field-error name="applicable_services" :form="$errorForm" />
    <x-owner.field-error name="applicable_services.*" :form="$errorForm" />
</fieldset>

{{-- FR-VOUCHER-006 (D-026): promo jam sepi, opsional --}}
<fieldset class="space-y-2">
    <legend class="text-xs font-semibold text-bq-text">Hanya pada hari dan jam tertentu</legend>
    <p class="text-xs text-bq-text-muted">Kosongkan untuk semua hari dan jam. Promo berlaku bila semua slot booking dimulai di dalam rentang ini.</p>
    <input type="hidden" name="valid_days" value="">
    <div class="flex flex-wrap gap-x-3 gap-y-1">
        @foreach ([1 => 'Sen', 2 => 'Sel', 3 => 'Rab', 4 => 'Kam', 5 => 'Jum', 6 => 'Sab', 7 => 'Min'] as $day => $dayLabel)
            <label class="inline-flex items-center gap-1.5 text-sm text-bq-text">
                <input type="checkbox" name="valid_days[]" value="{{ $day }}" x-model="{{ $model }}.valid_days">
                <span>{{ $dayLabel }}</span>
            </label>
        @endforeach
    </div>
    <div class="grid grid-cols-2 gap-3">
        <label class="block space-y-1 text-xs font-semibold text-bq-text">
            <span>Mulai jam</span>
            <input type="time" name="valid_from_time" step="300" x-model="{{ $model }}.valid_from_time" class="bq-input w-full">
            <x-owner.field-error name="valid_from_time" :form="$errorForm" />
        </label>
        <label class="block space-y-1 text-xs font-semibold text-bq-text">
            <span>Sampai jam</span>
            <input type="time" name="valid_until_time" step="300" x-model="{{ $model }}.valid_until_time" class="bq-input w-full">
            <x-owner.field-error name="valid_until_time" :form="$errorForm" />
        </label>
    </div>
    <x-owner.field-error name="valid_days.*" :form="$errorForm" />
</fieldset>
