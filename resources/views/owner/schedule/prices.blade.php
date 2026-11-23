{{-- BQ-162: Harga tab as in the Papan Jadwal prototype (#/jam-buka/harga): rules on the left, the simulator on the right. --}}
<div class="bq-split">
    <div class="grid min-w-0 gap-4">
        <x-ui.panel title="Akhir pekan" description="Berlaku Sabtu dan Minggu, di atas harga dasar dari Layanan.">
            <form method="POST" action="{{ route('owner.schedule.availability', ['tab' => 'prices']) }}" class="grid gap-4"
                x-data="{ type: @js(old('weekend_price_type', $tenant->weekend_price_type ?? 'none')) }">
                @csrf
                <x-ui.segmented label="Jenis aturan" name="weekend_price_type" @change="type = $event.target.value"
                    :options="['none' => 'Tanpa aturan', 'multiplier' => 'Dikali', 'fixed' => 'Harga tetap']"
                    :selected="old('weekend_price_type', $tenant->weekend_price_type ?? 'none')" />
                <div class="grid gap-1.5" x-show="type !== 'none'" x-cloak>
                    <label for="weekend-price-value" class="bq-label" x-text="type === 'multiplier' ? 'Pengali' : 'Harga tetap'"></label>
                    <div class="flex min-h-bq-control items-center rounded-bq-control border border-bq-border-strong bg-bq-surface focus-within:border-bq-primary focus-within:ring-2 focus-within:ring-bq-primary/20">
                        <span x-show="type === 'fixed'" class="pl-3 text-bq-form text-bq-text-muted">Rp</span>
                        <input id="weekend-price-value" name="weekend_price_value" type="number" min="0" step="0.01" inputmode="decimal"
                            :disabled="type === 'none'" :required="type !== 'none'" value="{{ old('weekend_price_value', $tenant->weekend_price_value) }}"
                            class="bq-num min-w-0 flex-1 border-0 bg-transparent px-3 text-bq-form outline-none focus:ring-0">
                        <span x-show="type === 'multiplier'" class="px-3 text-bq-form text-bq-text-muted">× harga dasar</span>
                    </div>
                    <p class="bq-hint" x-show="type === 'multiplier'">1,2 = harga dasar ditambah 20%. 1 = sama dengan hari biasa.</p>
                    <p class="bq-hint" x-show="type === 'fixed'" x-cloak>Harga per slot pada Sabtu dan Minggu, menggantikan harga dasar.</p>
                    <x-owner.field-error name="weekend_price_value" />
                </div>
                <div><x-ui.button type="submit" size="sm">Simpan aturan akhir pekan</x-ui.button></div>
            </form>
        </x-ui.panel>

        <x-ui.panel title="Jam ramai" description="Tambahan harga pada jam tertentu." flush>
            <x-slot:actions>
                <x-ui.button size="sm" icon="plus" x-data x-on:click="$dispatch('open-dialog', { id: 'peak-rule-new', trigger: $el })">Tambah</x-ui.button>
            </x-slot:actions>
            @if ($peakRows === [])
                <p class="px-5 py-4 text-bq-small text-bq-text-muted">Belum ada aturan jam ramai. Semua jam memakai harga dasar.</p>
            @else
                <x-ui.table class="rounded-none border-0" label="Aturan jam ramai" :columns="['Nama', 'Kapan', 'Tambahan', 'Layanan']" actions>
                    @foreach ($peakRows as $row)
                        <x-ui.table.row>
                            <x-ui.table.cell phone="main" :title="$row['rule']->name">
                                <button type="button" class="truncate text-left font-semibold text-bq-ink hover:text-bq-primary" x-data x-on:click="$dispatch('open-dialog', { id: 'peak-rule-{{ $row['rule']->id }}', trigger: $el })">{{ $row['rule']->name }}</button>
                            </x-ui.table.cell>
                            <x-ui.table.cell phone="subtitle">{{ $row['days'] }} · <span class="bq-num font-bq-display font-semibold">{{ $row['time'] }}</span></x-ui.table.cell>
                            <x-ui.table.cell phone="end" align="end"><span class="bq-num">{{ $row['extra'] }}</span></x-ui.table.cell>
                            <x-ui.table.cell :title="$row['services_title']"><span class="truncate text-bq-text-muted">{{ $row['services'] }}</span></x-ui.table.cell>
                            <x-slot:action>
                                <x-ui.button size="sm" variant="ghost" icon="edit" icon-only aria-label="Ubah {{ $row['rule']->name }}" x-data x-on:click="$dispatch('open-dialog', { id: 'peak-rule-{{ $row['rule']->id }}', trigger: $el })" />
                            </x-slot:action>
                        </x-ui.table.row>
                    @endforeach
                </x-ui.table>
            @endif
        </x-ui.panel>

        <x-ui.panel title="Tanggal khusus" description="Menggantikan harga pada tanggal tertentu." flush>
            @if ($daftarlayanan->isNotEmpty())
                <x-slot:actions>
                    <x-ui.button size="sm" icon="plus" x-data x-on:click="$dispatch('open-dialog', { id: 'special-date-new', trigger: $el })">Tambah</x-ui.button>
                </x-slot:actions>
            @endif
            @if ($specialDates->isEmpty())
                <p class="px-5 py-4 text-bq-small text-bq-text-muted">Belum ada tanggal khusus.</p>
            @else
                <x-ui.table class="rounded-none border-0" label="Harga tanggal khusus" :columns="['Tanggal', 'Harga', 'Layanan']" actions>
                    @foreach ($specialDates as $special)
                        <x-ui.table.row>
                            <x-ui.table.cell phone="main">{{ $special->tanggal->locale('id')->translatedFormat('j M Y') }}</x-ui.table.cell>
                            <x-ui.table.cell phone="end"><span class="bq-num">{{ \App\Support\Rupiah::format($special->price) }}</span></x-ui.table.cell>
                            <x-ui.table.cell phone="subtitle" :title="$special->service?->namalayanan"><span class="truncate text-bq-text-muted">{{ $special->service?->namalayanan }}</span></x-ui.table.cell>
                            <x-slot:action>
                                <form method="POST" id="delete-special-{{ $special->id }}" x-data action="{{ route('owner.schedule.special-dates.destroy', $special) }}">
                                    @csrf
                                    @method('DELETE')
                                    <x-ui.button size="sm" variant="ghost" icon="trash" icon-only aria-label="Hapus harga khusus {{ $special->tanggal->format('d/m/Y') }}"
                                        @click="$dispatch('open-confirm', { title: 'Hapus harga khusus?', message: 'Tanggal ini kembali mengikuti aturan harga lain yang berlaku.', formId: $el.form.id })" />
                                </form>
                            </x-slot:action>
                        </x-ui.table.row>
                    @endforeach
                </x-ui.table>
            @endif
        </x-ui.panel>
    </div>

    @if ($daftarlayanan->isNotEmpty())
        <x-ui.panel title="Simulasi harga" description="Harga tiap jam dan aturan yang membentuknya." class="md:sticky md:top-20">
            <form id="price-simulator" action="{{ route('owner.schedule.price-simulator') }}" method="GET" x-data="schedulePriceSimulator(@js((string) $daftarlayanan->first()->id))"
                x-init="$nextTick(() => simulate($el))" @change="simulate($el)" @submit.prevent="simulate($el)" class="grid gap-4">
                <div class="grid gap-1.5">
                    <label for="simulator-service" class="bq-label">Layanan</label>
                    <select id="simulator-service" name="idlayanan" class="bq-input w-full" required x-model="serviceId">
                        @foreach ($daftarlayanan as $service)
                            <option value="{{ $service->id }}">{{ $service->namalayanan }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid gap-1.5">
                    <label for="simulator-date" class="bq-label">Tanggal</label>
                    <input id="simulator-date" type="date" name="tanggal" required class="bq-input w-full" value="{{ now('Asia/Jakarta')->next(\Carbon\CarbonInterface::SATURDAY)->toDateString() }}">
                </div>
                <p x-show="error" x-cloak x-text="error" role="alert" class="text-bq-small text-bq-danger"></p>
                <p x-show="busy" x-cloak class="text-bq-small text-bq-text-muted">Menghitung…</p>
                <dl class="grid grid-cols-[64px_minmax(0,1fr)] gap-x-4 gap-y-2" aria-live="polite">
                    <template x-for="hour in hours" :key="hour.time">
                        <div class="contents">
                            <dt class="bq-num font-bq-display font-semibold text-bq-text-muted" x-text="hour.time.replace(':', '.')"></dt>
                            <dd>
                                <b class="bq-num" x-text="rupiah(hour.price)"></b>
                                <span class="block text-bq-small text-bq-text-muted" x-text="hour.rule_label"></span>
                            </dd>
                        </div>
                    </template>
                </dl>
                <p x-show="!busy && !error && hours.length === 0" class="text-bq-small text-bq-text-muted">Tidak ada jam buka pada tanggal ini.</p>
                <input type="hidden" name="idlayanan" form="apply-base-price" :value="serviceId">
            </form>
            <form method="POST" action="{{ route('owner.schedule.apply-base-price') }}" id="apply-base-price" x-data class="mt-4">
                @csrf
                <x-ui.button icon="check" class="w-full" @click="$dispatch('open-confirm', { title: 'Terapkan harga dasar?', message: 'Harga khusus per slot dihapus hanya untuk slot mendatang yang masih bebas pada layanan ini. Aturan harga tetap berlaku.', formId: 'apply-base-price' })">Terapkan harga dasar ke slot terbuka</x-ui.button>
            </form>
        </x-ui.panel>
    @endif
</div>

{{-- Forms open in the side panel, as in the prototype. --}}
<x-owner.drawer id="peak-rule-new" title="Tambah jam ramai">
    @include('owner.schedule.peak-rule', ['rule' => null])
</x-owner.drawer>
@foreach ($peakRules as $rule)
    <x-owner.drawer id="peak-rule-{{ $rule->id }}" title="Ubah jam ramai">
        @include('owner.schedule.peak-rule', ['rule' => $rule])
        <form method="POST" id="delete-peak-{{ $rule->id }}" x-data action="{{ route('owner.schedule.peak-rules.destroy', $rule) }}" class="mt-4 border-t border-bq-border pt-4">
            @csrf
            @method('DELETE')
            <x-ui.button variant="danger" size="sm" icon="trash"
                @click="$dispatch('open-confirm', { title: 'Hapus aturan jam ramai?', message: 'Tambahan harga dari aturan ini akan berhenti berlaku.', formId: $el.form.id })">Hapus aturan</x-ui.button>
        </form>
    </x-owner.drawer>
@endforeach
@if ($daftarlayanan->isNotEmpty())
    <x-owner.drawer id="special-date-new" title="Tambah tanggal khusus">
        <form method="POST" action="{{ route('owner.schedule.special-dates.store') }}" class="grid gap-4">
            @csrf
            <div class="grid grid-cols-2 gap-3">
                <div class="grid gap-1.5">
                    <label for="special-date" class="bq-label">Tanggal</label>
                    <input id="special-date" type="date" name="tanggal" required class="bq-input w-full" value="{{ old('tanggal') }}">
                    <x-owner.field-error name="tanggal" />
                </div>
                <div class="grid gap-1.5">
                    <label for="special-price" class="bq-label">Harga (Rp)</label>
                    <input id="special-price" type="number" name="price" required min="0" max="100000000" inputmode="numeric" class="bq-input w-full" value="{{ old('price') }}">
                    <x-owner.field-error name="price" />
                </div>
            </div>
            <fieldset class="grid gap-2">
                <legend class="bq-label mb-1">Layanan</legend>
                @foreach ($daftarlayanan as $service)
                    <x-ui.checkbox name="service_ids[]" value="{{ $service->id }}" :label="$service->namalayanan" :checked="in_array($service->id, (array) old('service_ids', []))" />
                @endforeach
                <x-owner.field-error name="service_ids" />
                <x-owner.field-error name="service_ids.*" />
            </fieldset>
            <div class="flex justify-end"><x-ui.button type="submit" variant="primary">Simpan tanggal khusus</x-ui.button></div>
        </form>
    </x-owner.drawer>
@endif
