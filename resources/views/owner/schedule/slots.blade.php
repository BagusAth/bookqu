<div class="space-y-4" x-data="{ slotIds: [], operation: 'close' }">
    @if ($daftarlayanan->isNotEmpty())
        <details class="rounded-xl border border-bq-border bg-bq-surface p-4" @if (request()->boolean('buat')) open @endif>
            <summary class="cursor-pointer font-semibold">Buat slot sekaligus</summary>
            <form class="mt-4 space-y-3" method="POST" action="{{ route('owner.schedule.bulk-store', ['tab' => 'slots']) }}"
                data-preview-url="{{ route('owner.schedule.bulk-preview') }}"
                x-data="scheduleBulkPreview(@js($openingHours))" @input="preview = null; openingChoices = []; openingWarning = ''" @change="preview = null" @submit="if (!canSave($el)) $event.preventDefault()">
                @csrf
                <div class="grid gap-3 sm:grid-cols-3">
                    <label class="grid gap-1 text-sm">Layanan
                        <select name="idlayanan" class="bq-input" @change="$refs.interval.value = $event.target.selectedOptions[0].dataset.duration" required>
                            <option value="">Pilih layanan</option>
                            @foreach ($daftarlayanan as $service)
                                <option value="{{ $service->id }}" data-duration="{{ $service->durasi }}">{{ $service->namalayanan }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="grid gap-1 text-sm">Periode
                        <select name="jenisslot" x-model="period" class="bq-input">
                            <option value="harian">Satu tanggal</option>
                            <option value="rentang">Rentang tanggal</option>
                        </select>
                    </label>
                    <label class="grid gap-1 text-sm" x-show="period === 'harian'">Tanggal
                        <input type="date" name="tanggal" :disabled="period !== 'harian'" required class="bq-input">
                    </label>
                    <label class="grid gap-1 text-sm" x-show="period === 'rentang'" x-cloak>Tanggal mulai
                        <input type="date" name="tanggalmulai" :disabled="period !== 'rentang'" required class="bq-input">
                    </label>
                    <label class="grid gap-1 text-sm" x-show="period === 'rentang'" x-cloak>Tanggal akhir
                        <input type="date" name="tanggalselesai" :disabled="period !== 'rentang'" required class="bq-input">
                    </label>
                    <label class="grid gap-1 text-sm">Jam mulai
                        <input type="time" name="jammulai" required class="bq-input">
                    </label>
                    <label class="grid gap-1 text-sm">Jam akhir
                        <input type="time" name="jamselesai" required class="bq-input">
                    </label>
                    <label class="grid gap-1 text-sm">Interval (menit)
                        <input type="number" name="intervalslot" x-ref="interval" min="5" max="480" step="5" required class="bq-input">
                    </label>
                </div>
                <div class="space-y-2 text-sm">
                    <button type="button" @click="useOpeningHours($el.form)" class="bq-btn bq-btn-secondary">Isi dari jam buka</button>
                    <p x-show="period === 'rentang'" class="text-bq-text-muted">Menggunakan jam buka pada tanggal mulai.</p>
                    <p x-show="openingWarning" class="text-bq-warning" x-text="openingWarning" role="status"></p>
                    <div x-show="openingChoices.length > 1" class="flex flex-wrap items-center gap-2">
                        <span>Pilih rentang</span>
                        <template x-for="interval in openingChoices" :key="interval.opens_at">
                            <button type="button" class="bq-btn bq-btn-ghost bq-btn-sm"
                                @click="applyOpeningHours($el.form, interval)" x-text="interval.opens_at + ' – ' + interval.closes_at"></button>
                        </template>
                    </div>
                </div>
                <p class="text-sm" role="status" x-show="preview" x-cloak x-text="preview && (preview.to_create + ' slot baru, ' + preview.already_exist + ' slot sudah ada.')"></p>
                <p class="text-sm" role="alert" x-show="error" x-cloak x-text="error"></p>
                <button type="button" :disabled="busy" @click="check($el.form)" class="bq-btn bq-btn-secondary">Pratinjau slot</button>
                <button disabled :disabled="!preview || !preview.to_create || busy" class="bq-btn bq-btn-primary disabled:opacity-50">Simpan slot</button>
            </form>
        </details>
    @endif
    <form method="GET" action="{{ route('owner.schedule') }}" class="flex flex-wrap items-end gap-3">
        <input type="hidden" name="tab" value="slots">
        <input type="hidden" name="minggu" value="{{ $offsetminggu }}">
        <label class="grid gap-1 text-sm">Layanan
            <select name="layanan" @change="$el.form.requestSubmit()" class="bq-input">
                <option value="">Semua layanan</option>
                @foreach ($daftarlayanan as $service)
                    <option value="{{ $service->id }}" @selected($service->id === $selectedService)>{{ $service->namalayanan }}</option>
                @endforeach
            </select>
        </label>
        <noscript><button class="bq-btn bq-btn-secondary">Terapkan</button></noscript>
    </form>
    <nav class="flex flex-wrap items-center gap-3 text-sm" aria-label="Minggu slot">
        <a class="text-bq-primary underline" href="{{ request()->fullUrlWithQuery(['minggu' => $offsetminggu - 1, 'buat' => null]) }}">Minggu sebelumnya</a>
        <span>{{ $awalminggu->format('d/m/Y') }} – {{ $akhirminggu->format('d/m/Y') }}</span>
        <a class="text-bq-primary underline" href="{{ request()->fullUrlWithQuery(['minggu' => $offsetminggu + 1, 'buat' => null]) }}">Minggu berikutnya</a>
    </nav>
    @if ($slots->contains(fn ($slot) => ! $slot->occupied))
        <form id="selected-slots" method="POST" action="{{ route('owner.schedule.slots.bulk') }}" class="flex flex-wrap items-center gap-3 rounded-xl border border-bq-border bg-bq-surface p-3 text-sm">
            @csrf
            <input type="hidden" name="operation" :value="operation">
            <span x-text="slotIds.length + ' slot dipilih'"></span>
            @foreach (['close' => 'Tutup slot pilihan', 'delete' => 'Hapus slot pilihan'] as $value => $label)
                <button type="button" :disabled="!slotIds.length" class="bq-btn bq-btn-secondary disabled:opacity-40"
                    @click="operation = @js($value); $dispatch('open-confirm', { title: @js($label.'?'), message: 'Tindakan akan diterapkan ke semua slot yang dipilih.', formId: $el.form.id, tone: @js($value === 'delete' ? 'danger' : 'primary'), confirmText: @js($label) })">{{ $label }}</button>
            @endforeach
            <x-owner.field-error name="slot_ids" />
        </form>
    @endif
    @forelse ($slots as $slot)
        <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-bq-border bg-bq-surface p-3 text-sm">
            <div>
                @if (! $slot->occupied)
                    <input type="checkbox" name="slot_ids[]" form="selected-slots" value="{{ $slot->id }}" x-model="slotIds"
                        aria-label="Pilih slot {{ $slot->layanan?->namalayanan }} {{ $slot->tanggal->format('d/m/Y') }} {{ $slotTimes[$slot->id] }}">
                @endif
                <strong>{{ $slot->layanan?->namalayanan ?? 'Layanan dihapus' }}</strong>
                <p class="tabular-nums">{{ $slot->tanggal->format('d/m/Y') }} · {{ $slotTimes[$slot->id] }}</p>
            </div>
            @isset($slotPrices[$slot->id])
                <span class="tabular-nums">Rp {{ number_format($slotPrices[$slot->id], 0, ',', '.') }}</span>
            @endisset
            <span>{{ $slotLabels[$slot->id] }}</span>
            @if ($slot->occupied)
                <div class="flex flex-wrap gap-2">
                    @foreach ($slot->bookings as $booking)
                        @include('owner.partials.bookings.panel-link')
                    @endforeach
                </div>
            @else
                <form method="POST" action="{{ route('owner.schedule.slots.update', $slot) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" name="operation" value="{{ $slot->status === 'tersedia' ? 'close' : 'open' }}"
                        class="bq-btn bq-btn-secondary">{{ $slot->status === 'tersedia' ? 'Tutup slot' : 'Buka slot' }}</button>
                </form>
                <form method="POST" action="{{ route('owner.schedule.slots.update', $slot) }}" class="flex flex-wrap items-end gap-2">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="operation" value="price">
                    <label class="grid gap-1 text-xs">Harga khusus (Rp)
                        <input name="harga_override" type="number" min="0" max="9999999999" step="1" value="{{ $slot->harga_override === null ? '' : (int) $slot->harga_override }}"
                            placeholder="Ikuti aturan harga" class="bq-input w-36">
                    </label>
                    <button class="bq-btn bq-btn-secondary">Simpan harga</button>
                </form>
            @endif
        </div>
    @empty
        <x-owner.empty-state title="Belum ada slot minggu ini" description="Buka slot agar pelanggan dapat memilih jadwal." />
    @endforelse
</div>
