{{-- BQ-162: Slot tab as in the Papan Jadwal prototype (#/jam-buka/slot): one panel with a filter row, rows grouped by day. --}}
<div x-data="{ slotIds: [], operation: 'close', search: '' }">
    <section class="bq-panel min-w-0">
        <form method="GET" action="{{ route('owner.schedule') }}" class="bq-filter">
            <input type="hidden" name="tab" value="slots">
            <input type="hidden" name="minggu" value="{{ $offsetminggu }}">
            <x-ui.search label="Cari jam" x-model="search" class="max-w-72" />
            <select name="layanan" aria-label="Layanan" @change="$el.form.requestSubmit()" class="bq-input max-w-64">
                <option value="">Semua layanan</option>
                @foreach ($daftarlayanan as $service)
                    <option value="{{ $service->id }}" @selected($service->id === $selectedService)>{{ $service->namalayanan }}</option>
                @endforeach
            </select>
            <noscript><button class="bq-btn bq-btn-secondary bq-btn-sm">Terapkan</button></noscript>
            <x-ui.date-nav :label="$weekLabel"
                :previous="request()->fullUrlWithQuery(['minggu' => $offsetminggu - 1, 'buat' => null])"
                :today="request()->fullUrlWithQuery(['minggu' => 0, 'buat' => null])"
                :next="request()->fullUrlWithQuery(['minggu' => $offsetminggu + 1, 'buat' => null])" />
        </form>

        @if ($slots->contains(fn ($slot) => ! $slot->occupied))
            <form id="selected-slots" method="POST" action="{{ route('owner.schedule.slots.bulk') }}" x-show="slotIds.length" x-cloak
                class="flex flex-wrap items-center gap-2 border-b border-bq-border bg-bq-primary-light px-5 py-2 text-bq-small">
                @csrf
                <input type="hidden" name="operation" :value="operation">
                <span class="font-semibold" x-text="slotIds.length + ' dipilih'"></span>
                @foreach (['close' => 'Tutup slot pilihan', 'delete' => 'Hapus slot pilihan'] as $value => $label)
                    <button type="button" class="bq-btn bq-btn-secondary bq-btn-sm"
                        @click="operation = @js($value); $dispatch('open-confirm', { title: @js($label.'?'), message: 'Tindakan akan diterapkan ke semua slot yang dipilih.', formId: $el.form.id, tone: @js($value === 'delete' ? 'danger' : 'primary'), confirmText: @js($label) })">{{ $label }}</button>
                @endforeach
                <button type="button" class="bq-btn bq-btn-ghost bq-btn-sm ml-auto" @click="slotIds = []">Batal pilih</button>
                <x-owner.field-error name="slot_ids" />
            </form>
        @endif

        @if ($slots->isNotEmpty())
            <x-ui.table class="rounded-none border-0" :columns="['Jam', 'Status', 'Harga']" widths="minmax(140px, auto) minmax(200px, 1fr) minmax(0, 1.4fr)" label="Slot minggu ini" selectable actions>
                @foreach ($slots->groupBy(fn ($slot) => $slot->tanggal->toDateString()) as $date => $daySlots)
                    <div role="row" class="bq-table-row bq-table-group">
                        <div role="cell" class="col-span-full">{{ $dayHeadings[$date] }}</div>
                    </div>
                    @foreach ($daySlots as $slot)
                        @php $state = $slotStates[$slot->id]; @endphp
                        <div role="row" @class(['bq-table-row', 'text-bq-text-muted' => $state['state'] === 'past'])
                            x-show="!search || @js($slotTimes[$slot->id].' '.$slot->layanan?->namalayanan).toLowerCase().includes(search.toLowerCase())">
                            <div role="cell" class="bq-table-choice">
                                @if (! $slot->occupied && $state['state'] !== 'past')
                                    <input type="checkbox" name="slot_ids[]" form="selected-slots" value="{{ $slot->id }}" x-model="slotIds" class="size-4 accent-bq-primary"
                                        aria-label="Pilih slot {{ $slot->layanan?->namalayanan }} {{ $slot->tanggal->format('d/m/Y') }} {{ $slotTimes[$slot->id] }}">
                                @endif
                            </div>
                            <div role="cell" class="bq-table-cell bq-table-main min-w-0">
                                <strong class="bq-num font-bq-display text-bq-clock font-semibold whitespace-nowrap">{{ str_replace(':', '.', $slotTimes[$slot->id]) }}</strong>
                                @if ($selectedService === 0)
                                    <span class="bq-table-caption block truncate text-bq-small text-bq-text-muted" title="{{ $slot->layanan?->namalayanan }}">{{ $slot->layanan?->namalayanan ?? 'Layanan dihapus' }}</span>
                                @endif
                            </div>
                            <div role="cell" class="bq-table-cell bq-table-sub min-w-0 truncate" title="{{ $state['label'] }}">
                                @if ($state['tone'])
                                    <x-ui.status-dot :tone="$state['tone']">{{ $state['label'] }}</x-ui.status-dot>
                                @else
                                    <span @class(['text-bq-primary' => $state['state'] === 'open'])>{{ $state['label'] }}</span>
                                @endif
                            </div>
                            <div role="cell" class="bq-table-cell bq-table-end min-w-0 truncate">
                                @isset($slotPrices[$slot->id])
                                    <span @class(['bq-num', 'text-bq-primary' => $state['state'] !== 'past'])>{{ \App\Support\Rupiah::format($slotPrices[$slot->id]) }}</span>
                                    @if ($slotPriceNotes[$slot->id]['note'] ?? null)
                                        <span class="hidden text-bq-text-muted md:inline"> · {{ $slotPriceNotes[$slot->id]['note'] }}</span>
                                    @endif
                                @endisset
                            </div>
                            <div role="cell" class="bq-table-action">
                                @if ($state['state'] !== 'past')
                                    <x-ui.menu :id="'slot-actions-'.$slot->id" :label="'Tindakan slot '.$slotTimes[$slot->id]">
                                        @if ($slot->occupied)
                                            @foreach ($slot->bookings as $booking)
                                                <button role="menuitem" tabindex="-1" type="button"
                                                    @click="$dispatch('open-booking-panel', { url: @js(route('owner.bookings.panel', $booking->id)), trigger: $el })">Lihat booking {{ $booking->namapelanggan }}</button>
                                            @endforeach
                                        @else
                                            <form method="POST" action="{{ route('owner.schedule.slots.update', $slot) }}" class="contents">
                                                @csrf
                                                @method('PATCH')
                                                <button role="menuitem" tabindex="-1" type="submit" name="operation" value="{{ $slot->status === 'tersedia' ? 'close' : 'open' }}">{{ $slot->status === 'tersedia' ? 'Tutup slot ini' : 'Buka lagi' }}</button>
                                            </form>
                                            <button role="menuitem" tabindex="-1" type="button" @click="$dispatch('open-dialog', { id: 'slot-price-{{ $slot->id }}', trigger: $el })">Ubah harga slot ini</button>
                                        @endif
                                    </x-ui.menu>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @endforeach
            </x-ui.table>
            <p class="bq-pager">{{ $slots->count() }} slot pada {{ $weekLabel }}</p>
        @else
            <x-ui.empty title="Belum ada slot minggu ini" description="Buka slot agar pelanggan dapat memilih jadwal." icon="calendar" />
        @endif
    </section>

    @foreach ($slots->reject(fn ($slot) => $slot->occupied) as $slot)
        <x-owner.dialog :id="'slot-price-'.$slot->id" title="Ubah harga slot ini">
            <form method="POST" action="{{ route('owner.schedule.slots.update', $slot) }}" class="grid gap-4">
                @csrf
                @method('PATCH')
                <input type="hidden" name="operation" value="price">
                <p class="text-bq-body text-bq-text-muted">{{ $slot->layanan?->namalayanan }} · {{ $slot->tanggal->locale('id')->translatedFormat('j M') }} · {{ str_replace(':', '.', $slotTimes[$slot->id]) }}</p>
                <div class="grid gap-1.5">
                    <label for="slot-price-input-{{ $slot->id }}" class="bq-label">Harga khusus</label>
                    <div class="flex min-h-bq-control items-center rounded-bq-control border border-bq-border-strong bg-bq-surface focus-within:border-bq-primary focus-within:ring-2 focus-within:ring-bq-primary/20">
                        <span class="pl-3 text-bq-form text-bq-text-muted">Rp</span>
                        <input id="slot-price-input-{{ $slot->id }}" name="harga_override" type="number" min="0" max="9999999999" step="1" inputmode="numeric"
                            value="{{ $slot->harga_override === null ? '' : (int) $slot->harga_override }}" placeholder="Ikuti aturan harga"
                            class="bq-num min-w-0 flex-1 border-0 bg-transparent px-3 text-bq-form outline-none focus:ring-0">
                    </div>
                    <p class="bq-hint">Kosongkan untuk mengikuti harga dasar dan aturan harga.</p>
                </div>
                <div class="flex justify-end gap-2">
                    <x-ui.button x-on:click="$dispatch('close-dialog', { id: 'slot-price-{{ $slot->id }}' })">Batal</x-ui.button>
                    <x-ui.button type="submit" variant="primary">Simpan harga</x-ui.button>
                </div>
            </form>
        </x-owner.dialog>
    @endforeach
</div>

@if ($daftarlayanan->isNotEmpty())
    <x-owner.dialog id="schedule-bulk-dialog" title="Buka slot sekaligus">
        <form class="grid gap-4" method="POST" action="{{ route('owner.schedule.bulk-store', ['tab' => 'slots']) }}"
            data-preview-url="{{ route('owner.schedule.bulk-preview') }}"
            x-data="scheduleBulkPreview(@js($openingHours))"
            @if (request()->boolean('buat')) x-init="$nextTick(() => $dispatch('open-dialog', { id: 'schedule-bulk-dialog' }))" @endif
            @input="preview = null; openingChoices = []; openingWarning = ''" @change="preview = null" @submit="if (!canSave($el)) $event.preventDefault()">
            @csrf
            <div class="grid gap-1.5">
                <label for="bulk-service" class="bq-label">Layanan <span class="text-bq-danger" aria-hidden="true">*</span></label>
                <select id="bulk-service" name="idlayanan" class="bq-input w-full" @change="$refs.interval.value = $event.target.selectedOptions[0].dataset.duration" required>
                    <option value="">Pilih layanan</option>
                    @foreach ($daftarlayanan as $service)
                        <option value="{{ $service->id }}" data-duration="{{ $service->durasi }}" @selected($service->id === request()->integer('layanan'))>{{ $service->namalayanan }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid gap-1.5">
                <span class="bq-label">Tanggal</span>
                <x-ui.segmented label="Periode" name="jenisslot" :options="['harian' => 'Satu hari', 'rentang' => 'Rentang']" selected="harian" @change="period = $event.target.value" />
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="grid gap-1.5" x-show="period === 'harian'">
                    <label for="bulk-date" class="bq-label">Tanggal</label>
                    <input id="bulk-date" type="date" name="tanggal" :disabled="period !== 'harian'" required class="bq-input w-full">
                </div>
                <div class="grid gap-1.5" x-show="period === 'rentang'" x-cloak>
                    <label for="bulk-from" class="bq-label">Dari</label>
                    <input id="bulk-from" type="date" name="tanggalmulai" :disabled="period !== 'rentang'" required class="bq-input w-full">
                </div>
                <div class="grid gap-1.5" x-show="period === 'rentang'" x-cloak>
                    <label for="bulk-to" class="bq-label">Sampai</label>
                    <input id="bulk-to" type="date" name="tanggalselesai" :disabled="period !== 'rentang'" required class="bq-input w-full">
                    <p class="bq-hint">Paling jauh {{ $tenant->slot_open_days_ahead ?? $maxDaysAhead }} hari ke depan.</p>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="grid gap-1.5">
                    <label for="bulk-start" class="bq-label">Jam mulai</label>
                    <input id="bulk-start" type="time" name="jammulai" required class="bq-input w-full">
                </div>
                <div class="grid gap-1.5">
                    <label for="bulk-end" class="bq-label">Jam selesai</label>
                    <input id="bulk-end" type="time" name="jamselesai" required class="bq-input w-full">
                </div>
            </div>
            <div class="grid gap-2">
                <div class="flex flex-wrap items-center gap-2">
                    <x-ui.button size="sm" icon="clock" @click="useOpeningHours($el.form)">Isi dari jam buka</x-ui.button>
                    <span class="text-bq-small text-bq-text-muted">Jeda istirahat tetap dilewati.</span>
                </div>
                <p x-show="openingWarning" class="text-bq-small text-bq-warning" x-text="openingWarning" role="status"></p>
                <div x-show="openingChoices.length > 1" class="flex flex-wrap items-center gap-2 text-bq-small">
                    <span>Pilih rentang</span>
                    <template x-for="interval in openingChoices" :key="interval.opens_at">
                        <button type="button" class="bq-btn bq-btn-ghost bq-btn-sm" @click="applyOpeningHours($el.form, interval)" x-text="interval.opens_at + ' – ' + interval.closes_at"></button>
                    </template>
                </div>
            </div>
            <div class="grid gap-1.5">
                <label for="bulk-interval" class="bq-label">Jarak antar-slot</label>
                <div class="flex min-h-bq-control items-center rounded-bq-control border border-bq-border-strong bg-bq-surface focus-within:border-bq-primary focus-within:ring-2 focus-within:ring-bq-primary/20">
                    <input id="bulk-interval" type="number" name="intervalslot" x-ref="interval" min="5" max="480" step="5" required inputmode="numeric"
                        class="bq-num min-w-0 flex-1 border-0 bg-transparent px-3 text-bq-form outline-none focus:ring-0">
                    <span class="px-3 text-bq-form text-bq-text-muted">menit</span>
                </div>
                <p class="bq-hint">Bawaan sama dengan durasi layanan.</p>
            </div>
            <p class="flex gap-2 rounded-bq-control bg-bq-primary-light px-3 py-2 text-bq-small text-bq-ink" role="status" x-show="preview" x-cloak>
                <x-owner.icon name="info" class="size-4 shrink-0 text-bq-primary" />
                <span x-text="preview && (preview.to_create + ' slot baru akan dibuka. ' + preview.already_exist + ' slot pada rentang ini sudah ada dan tidak digandakan. Belum ada yang tersimpan.')"></span>
            </p>
            <p class="text-bq-small text-bq-danger" role="alert" x-show="error" x-cloak x-text="error"></p>
            <div class="flex flex-wrap justify-end gap-2">
                <x-ui.button x-bind:disabled="busy" @click="check($el.form)">Pratinjau</x-ui.button>
                <button disabled :disabled="!preview || !preview.to_create || busy" class="bq-btn bq-btn-primary"
                    x-text="preview && preview.to_create ? 'Buka ' + preview.to_create + ' slot' : 'Buka slot'">Buka slot</button>
            </div>
        </form>
    </x-owner.dialog>
@endif
