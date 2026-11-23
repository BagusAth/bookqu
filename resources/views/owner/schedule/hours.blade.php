{{-- BQ-162: Jam buka tab as in the Papan Jadwal prototype (#/jam-buka): automatic slots, then one row per day. --}}
<form class="bq-panel" x-data="scheduleOpeningHours(@js($openingHours))"
    action="{{ route('owner.schedule.opening-hours') }}" method="POST" @submit.prevent="save($event.currentTarget)">
    @csrf
    @method('PUT')
    <x-ui.form-row title="Slot otomatis" description="Sistem membuka slot dari jam buka setiap hari. Anda hanya mengatur pengecualian.">
        <div class="grid gap-1.5">
            <label for="automatic-slot-setting" class="bq-label">Buka slot otomatis</label>
            <select id="automatic-slot-setting" name="auto_open_slots" class="bq-input w-full">
                <option value="">Pertahankan pilihan per layanan</option>
                <option value="1">Aktifkan untuk semua layanan aktif</option>
                <option value="0">Nonaktifkan untuk semua layanan aktif</option>
            </select>
            <p class="bq-hint">{{ $overview['auto_open'] }} dari {{ $overview['services'] }} layanan ikut. <a class="bq-link" href="{{ route('owner.services') }}">Atur per layanan</a></p>
        </div>
        <div class="grid gap-1.5">
            <label for="slot-open-days-ahead" class="bq-label">Buka slot sampai</label>
            <div class="flex min-h-bq-control items-center rounded-bq-control border border-bq-border-strong bg-bq-surface focus-within:border-bq-primary focus-within:ring-2 focus-within:ring-bq-primary/20">
                <input id="slot-open-days-ahead" name="slot_open_days_ahead" type="number" min="1" max="365" inputmode="numeric"
                    value="{{ $tenant->slot_open_days_ahead }}" placeholder="{{ $maxDaysAhead }}"
                    class="bq-num min-w-0 flex-1 border-0 bg-transparent px-3 text-bq-form outline-none focus:ring-0">
                <span class="px-3 text-bq-form text-bq-text-muted">hari ke depan</span>
            </div>
            <p class="bq-hint">Pelanggan bisa booking paling jauh {{ $maxDaysAhead }} hari ke depan.</p>
        </div>
    </x-ui.form-row>

    <x-ui.form-row title="Jam buka mingguan" description="Satu baris per hari. Tambah jeda untuk istirahat.">
        <div class="bq-panel bq-weekly-hours">
            @foreach ($weekdays as $day => $label)
                <div class="bq-hours-row" :class="closed[{{ $day }}] && 'text-bq-text-muted'">
                    <b class="bq-hours-day font-semibold" :class="closed[{{ $day }}] ? 'text-bq-text-muted' : 'text-bq-ink'" >{{ $label }}</b>
                    <label class="bq-hours-switch relative inline-flex cursor-pointer">
                        <input type="checkbox" role="switch" class="peer sr-only" :checked="!closed[{{ $day }}]" @change="closed[{{ $day }}] = !$event.target.checked; toggle({{ $day }})" aria-label="{{ $label }} buka">
                        <span aria-hidden="true" class="relative h-5 w-9 rounded-full bg-bq-border-strong after:absolute after:top-0.5 after:left-0.5 after:size-4 after:rounded-full after:bg-bq-surface after:transition-transform peer-checked:bg-bq-primary peer-checked:after:translate-x-4 peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-bq-primary"></span>
                    </label>
                    <div class="bq-hours-ranges">
                        <span x-show="closed[{{ $day }}]" class="text-bq-text-muted">Tutup</span>
                        <template x-for="(interval, index) in days[{{ $day }}]" :key="interval.key">
                            <span class="bq-hours-range" x-show="!closed[{{ $day }}]">
                                <input class="bq-input" x-model="interval.opens_at" inputmode="numeric" maxlength="5" placeholder="08.00" :aria-label="index ? '{{ $label }} buka lagi' : '{{ $label }} buka'" :disabled="closed[{{ $day }}]" required>
                                <span class="text-bq-text-muted">–</span>
                                <input class="bq-input" x-model="interval.closes_at" inputmode="numeric" maxlength="5" placeholder="17.00" :aria-label="index ? '{{ $label }} tutup' : '{{ $label }} mulai jeda'" :disabled="closed[{{ $day }}]" required>
                                <button type="button" x-show="index > 0" class="bq-btn bq-btn-ghost bq-btn-sm bq-btn-icon !min-h-8 !min-w-8" @click="days[{{ $day }}].splice(index, 1)" aria-label="Hapus jeda {{ $label }}"><x-owner.icon name="x" class="h-4 w-4" /></button>
                            </span>
                        </template>
                    </div>
                    <span class="bq-hours-action justify-self-end">
                        <button type="button" x-show="!closed[{{ $day }}]" class="bq-btn bq-btn-ghost bq-btn-sm" :disabled="days[{{ $day }}].length >= 6" @click="add({{ $day }})">Tambah jeda</button>
                    </span>
                </div>
            @endforeach
        </div>
        <div>
            <x-ui.button size="sm" icon="copy" @click="copyToAll(1)">Salin Senin ke semua hari</x-ui.button>
        </div>
        <ul role="alert" x-show="errors.length" x-cloak class="space-y-1 text-bq-small text-bq-danger">
            <template x-for="(error, index) in errors" :key="index"><li x-text="error"></li></template>
        </ul>
    </x-ui.form-row>

    <x-ui.savebar>
        <a href="{{ route('owner.schedule', ['tab' => 'hours']) }}" class="bq-btn bq-btn-ghost">Batal</a>
        <button :disabled="busy" class="bq-btn bq-btn-primary">Simpan jam buka</button>
    </x-ui.savebar>
</form>
