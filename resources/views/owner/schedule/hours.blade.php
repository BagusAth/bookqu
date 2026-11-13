<form class="space-y-4 rounded-xl border border-bq-border bg-bq-surface p-4" x-data="scheduleOpeningHours(@js($openingHours))"
    action="{{ route('owner.schedule.opening-hours') }}" method="POST" @submit.prevent="save($event.currentTarget)">
    @csrf
    @method('PUT')
    <h2 class="font-semibold">Jam operasional mingguan</h2>
    <p class="text-sm text-bq-text-muted">Untuk jeda istirahat, tambahkan rentang baru setelah jeda. Jam buka tidak otomatis membuat slot.</p>
    @foreach ($weekdays as $day => $label)
        <fieldset class="space-y-2 border-t border-bq-border pt-2">
            <legend class="font-medium">{{ $label }}</legend>
            <template x-for="(interval, index) in days[{{ $day }}]" :key="interval.key">
                <div class="flex flex-wrap items-end gap-2">
                    <label class="grid text-sm">Buka <input class="rounded border border-bq-border bg-bq-surface p-2" type="time" x-model="interval.opens_at" required></label>
                    <label class="grid text-sm">Tutup <input class="rounded border border-bq-border bg-bq-surface p-2" type="time" x-model="interval.closes_at" required></label>
                    <button type="button" class="p-2 text-sm text-bq-primary" @click="days[{{ $day }}].splice(index, 1)">Hapus rentang</button>
                </div>
            </template>
            <button type="button" class="text-sm font-semibold text-bq-primary" :disabled="days[{{ $day }}].length >= 6" @click="add({{ $day }})">Tambah rentang</button>
        </fieldset>
    @endforeach
    <label class="grid max-w-xs gap-1 text-sm">Batas buka slot (hari ke depan)
        <input name="slot_open_days_ahead" class="rounded border border-bq-border bg-bq-surface p-2" type="number" min="1" max="365" value="{{ $tenant->slot_open_days_ahead }}">
    </label>
    <ul role="alert" x-show="errors.length" x-cloak class="text-sm">
        <template x-for="(error, index) in errors" :key="index"><li x-text="error"></li></template>
    </ul>
    <button :disabled="busy" class="rounded-lg bg-bq-primary px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">Simpan jam buka</button>
</form>
@if ($daftarlayanan->isNotEmpty())
    <p class="mt-4 text-sm text-bq-text-muted">Durasi dari <a class="underline" href="{{ route('owner.services') }}">Layanan</a>:
        {{ $daftarlayanan->map(fn ($service) => $service->namalayanan . ' (' . $service->durasi . ' menit)')->join(', ') }}.
    </p>
@endif
