<div x-show="{{ $model }}.discount_type === 'percentage'">
    <label class="block space-y-1 text-xs font-semibold text-bq-text">
        <span>Diskon maksimum (Rp)</span>
        <input type="number" name="max_discount_amount" min="0" step="0.01"
               x-model="{{ $model }}.max_discount_amount"
               placeholder="Kosongkan jika tanpa batas"
               class="w-full rounded-xl border border-bq-border px-3 py-2 text-xs">
    </label>
</div>

<fieldset class="space-y-2">
    <legend class="text-xs font-semibold text-bq-text">Berlaku untuk layanan</legend>
    <p class="text-xs text-bq-text-muted">Kosongkan pilihan untuk semua layanan.</p>
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
</fieldset>
