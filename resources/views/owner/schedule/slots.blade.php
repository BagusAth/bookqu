<div class="space-y-4">
    @if ($daftarlayanan->isNotEmpty())
        <details class="rounded-xl border border-bq-border bg-bq-surface p-4" @if (request()->boolean('buat')) open @endif>
            <summary class="cursor-pointer font-semibold">Buat slot sekaligus</summary>
            <form class="mt-4 space-y-3" method="POST" action="{{ route('owner.schedule.bulk-store', ['tab' => 'slots']) }}"
                data-preview-url="{{ route('owner.schedule.bulk-preview') }}"
                x-data="scheduleBulkPreview" @input="preview = null" @change="preview = null" @submit="if (!canSave($el)) $event.preventDefault()">
                @csrf
                <div class="grid gap-3 sm:grid-cols-3">
                    <label class="grid gap-1 text-sm">Layanan
                        <select name="idlayanan" class="rounded border border-bq-border bg-bq-surface p-2" @change="$refs.interval.value = $event.target.selectedOptions[0].dataset.duration" required>
                            <option value="">Pilih layanan</option>
                            @foreach ($daftarlayanan as $service)
                                <option value="{{ $service->id }}" data-duration="{{ $service->durasi }}">{{ $service->namalayanan }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="grid gap-1 text-sm">Periode
                        <select name="jenisslot" x-model="period" class="rounded border border-bq-border bg-bq-surface p-2">
                            <option value="harian">Satu tanggal</option>
                            <option value="rentang">Rentang tanggal</option>
                        </select>
                    </label>
                    <label class="grid gap-1 text-sm" x-show="period === 'harian'">Tanggal
                        <input type="date" name="tanggal" :disabled="period !== 'harian'" required class="rounded border border-bq-border bg-bq-surface p-2">
                    </label>
                    <label class="grid gap-1 text-sm" x-show="period === 'rentang'" x-cloak>Tanggal mulai
                        <input type="date" name="tanggalmulai" :disabled="period !== 'rentang'" required class="rounded border border-bq-border bg-bq-surface p-2">
                    </label>
                    <label class="grid gap-1 text-sm" x-show="period === 'rentang'" x-cloak>Tanggal akhir
                        <input type="date" name="tanggalselesai" :disabled="period !== 'rentang'" required class="rounded border border-bq-border bg-bq-surface p-2">
                    </label>
                    <label class="grid gap-1 text-sm">Jam mulai
                        <input type="time" name="jammulai" required class="rounded border border-bq-border bg-bq-surface p-2">
                    </label>
                    <label class="grid gap-1 text-sm">Jam akhir
                        <input type="time" name="jamselesai" required class="rounded border border-bq-border bg-bq-surface p-2">
                    </label>
                    <label class="grid gap-1 text-sm">Interval (menit)
                        <input type="number" name="intervalslot" x-ref="interval" min="5" max="480" step="5" required class="rounded border border-bq-border bg-bq-surface p-2">
                    </label>
                </div>
                <p class="text-sm" role="status" x-show="preview" x-cloak x-text="preview && (preview.to_create + ' slot baru, ' + preview.already_exist + ' slot sudah ada.')"></p>
                <p class="text-sm" role="alert" x-show="error" x-cloak x-text="error"></p>
                <button type="button" :disabled="busy" @click="check($el.form)" class="rounded-lg border border-bq-border px-4 py-2 text-sm font-semibold">Pratinjau slot</button>
                <button disabled :disabled="!preview || !preview.to_create || busy" class="rounded-lg bg-bq-primary px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">Simpan slot</button>
            </form>
        </details>
    @endif
    <form method="GET" action="{{ route('owner.schedule') }}" class="flex flex-wrap items-end gap-3">
        <input type="hidden" name="tab" value="slots">
        <input type="hidden" name="minggu" value="{{ $offsetminggu }}">
        <label class="grid gap-1 text-sm">Layanan
            <select name="layanan" class="rounded border border-bq-border bg-bq-surface p-2">
                <option value="">Semua layanan</option>
                @foreach ($daftarlayanan as $service)
                    <option value="{{ $service->id }}" @selected($service->id === $selectedService)>{{ $service->namalayanan }}</option>
                @endforeach
            </select>
        </label>
        <button class="rounded-lg border border-bq-border px-3 py-2 text-sm font-semibold">Terapkan</button>
    </form>
    <nav class="flex flex-wrap items-center gap-3 text-sm" aria-label="Minggu slot">
        <a class="text-bq-primary underline" href="{{ request()->fullUrlWithQuery(['minggu' => $offsetminggu - 1, 'buat' => null]) }}">Minggu sebelumnya</a>
        <span>{{ $awalminggu->format('d/m/Y') }} – {{ $akhirminggu->format('d/m/Y') }}</span>
        <a class="text-bq-primary underline" href="{{ request()->fullUrlWithQuery(['minggu' => $offsetminggu + 1, 'buat' => null]) }}">Minggu berikutnya</a>
    </nav>
    @forelse ($slots as $slot)
        <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-bq-border bg-bq-surface p-3 text-sm">
            <div>
                <strong>{{ $slot->layanan?->namalayanan ?? 'Layanan dihapus' }}</strong>
                <p class="tabular-nums">{{ $slot->tanggal->format('d/m/Y') }} · {{ $slotTimes[$slot->id] }}</p>
            </div>
            @isset($slotPrices[$slot->id])
                <span class="tabular-nums">Rp {{ number_format($slotPrices[$slot->id], 0, ',', '.') }}</span>
            @endisset
            <span>{{ $slotLabels[$slot->id] }}</span>
            @if (!$slot->occupied)
                <form method="POST" action="{{ route('owner.schedule.slots.destroy', $slot) }}" id="delete-schedule-{{ $slot->id }}" x-data>
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="return_to" value="slots">
                    <button type="button" class="text-bq-primary underline" @click="$dispatch('open-confirm', { title: 'Hapus slot?', message: 'Slot ini tidak dapat dipilih pelanggan lagi.', formId: @js('delete-schedule-' . $slot->id), tone: 'danger' })">Hapus slot</button>
                </form>
            @endif
        </div>
    @empty
        <x-owner.empty-state title="Belum ada slot minggu ini" description="Buka slot agar pelanggan dapat memilih jadwal." />
    @endforelse
</div>
