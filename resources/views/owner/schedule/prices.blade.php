<div class="space-y-4">
    <div class="rounded-xl border border-bq-border bg-bq-surface p-4">
        <h2 class="font-semibold">Harga dasar</h2>
        <p class="text-sm text-bq-text-muted">Ubah harga dasar di <a class="underline" href="{{ route('owner.services') }}">Layanan</a>.</p>
        <ul class="mt-2 space-y-1 text-sm">
            @foreach ($daftarlayanan as $service)
                <li>{{ $service->namalayanan }} · Rp {{ number_format($service->harga, 0, ',', '.') }}</li>
            @endforeach
        </ul>
        @if ($daftarlayanan->isNotEmpty())
            <form method="POST" action="{{ route('owner.schedule.apply-base-price') }}" id="apply-base-price" x-data class="mt-3 flex flex-wrap items-end gap-2">
                @csrf
                <label class="grid gap-1 text-sm">Layanan
        <select name="idlayanan" class="rounded border border-bq-border bg-bq-surface p-2" required>
            @foreach ($daftarlayanan as $service)
                <option value="{{ $service->id }}">{{ $service->namalayanan }}</option>
            @endforeach
        </select>
    </label>
                <button type="button" class="rounded-lg bg-bq-primary px-4 py-2 text-sm font-semibold text-white" @click="$dispatch('open-confirm', { title: 'Terapkan harga dasar?', message: 'Harga khusus per slot dihapus hanya untuk slot mendatang yang masih bebas. Aturan harga tetap berlaku.', formId: 'apply-base-price' })">Terapkan harga dasar ke slot terbuka</button>
            </form>
        @endif
    </div>

    <form method="POST" action="{{ route('owner.schedule.availability', ['tab' => 'prices']) }}" class="space-y-3 rounded-xl border border-bq-border bg-bq-surface p-4"
        x-data="{ type: @js(old('weekend_price_type', $tenant->weekend_price_type ?? 'none')) }">
        @csrf
        <h2 class="font-semibold">Harga akhir pekan</h2>
        <div class="flex flex-wrap items-end gap-3">
            <label class="grid gap-1 text-sm">Aturan
        <select name="weekend_price_type" x-model="type" class="rounded border border-bq-border bg-bq-surface p-2">
                    <option value="none">Harga biasa</option>
                    <option value="multiplier">Pengali harga dasar</option>
                    <option value="fixed">Harga tetap</option>
                </select>
    </label>
            <label class="grid gap-1 text-sm" x-show="type !== 'none'" x-cloak>
                <span x-text="type === 'multiplier' ? 'Pengali (contoh 1,5)' : 'Harga tetap (Rp)'"></span>
                <input name="weekend_price_value" type="number" min="0" step="0.01" :disabled="type === 'none'" :required="type !== 'none'" value="{{ old('weekend_price_value', $tenant->weekend_price_value) }}" class="rounded border border-bq-border bg-bq-surface p-2">
            </label>
            <button class="rounded-lg bg-bq-primary px-4 py-2 text-sm font-semibold text-white">Simpan harga akhir pekan</button>
        </div>
    </form>

    <section class="space-y-3 rounded-xl border border-bq-border bg-bq-surface p-4">
        <h2 class="font-semibold">Aturan jam sibuk</h2>
        @foreach ($peakRules as $rule)
            <details class="rounded border border-bq-border p-3">
                <summary class="cursor-pointer">{{ $rule->name }} · +Rp {{ number_format($rule->extra_amount, 0, ',', '.') }}</summary>
                @include('owner.schedule.peak-rule', ['rule' => $rule])
                <form method="POST" action="{{ route('owner.schedule.peak-rules.destroy', $rule) }}" class="mt-2">
                    @csrf
                    @method('DELETE')
                    <button class="text-sm text-bq-primary underline">Hapus aturan</button>
                </form>
            </details>
        @endforeach
        <details>
            <summary class="cursor-pointer text-sm font-semibold text-bq-primary">Tambah aturan jam sibuk</summary>
            @include('owner.schedule.peak-rule', ['rule' => null])
        </details>
    </section>

    @if ($daftarlayanan->isNotEmpty())
        <section class="space-y-3 rounded-xl border border-bq-border bg-bq-surface p-4">
            <h2 class="font-semibold">Harga tanggal khusus</h2>
            @foreach ($specialDates as $special)
                <form method="POST" action="{{ route('owner.schedule.special-dates.destroy', $special) }}" class="flex flex-wrap items-center justify-between gap-2 text-sm">
                    @csrf
                    @method('DELETE')
                    <span>{{ $special->tanggal->format('d/m/Y') }} · {{ $special->service?->namalayanan }} · Rp {{ number_format($special->price, 0, ',', '.') }}</span>
                    <button class="text-bq-primary underline">Hapus harga khusus</button>
                </form>
            @endforeach
            <form method="POST" action="{{ route('owner.schedule.special-dates.store') }}" class="grid gap-3 sm:grid-cols-3">
                @csrf
                <label class="grid gap-1 text-sm">Tanggal
        <input type="date" name="tanggal" required class="rounded border border-bq-border bg-bq-surface p-2">
    </label>
                <label class="grid gap-1 text-sm">Harga (Rp)
        <input type="number" name="price" required min="0" max="100000000" class="rounded border border-bq-border bg-bq-surface p-2">
    </label>
                <label class="grid gap-1 text-sm">Layanan
        <select name="service_ids[]" multiple required class="rounded border border-bq-border bg-bq-surface p-2">
                        @foreach ($daftarlayanan as $service)
                            <option value="{{ $service->id }}">{{ $service->namalayanan }}</option>
                        @endforeach
                    </select>
    </label>
                <div><button class="rounded-lg bg-bq-primary px-4 py-2 text-sm font-semibold text-white">Simpan harga khusus</button></div>
            </form>
        </section>

        <form action="{{ route('owner.schedule.price-simulator') }}" method="GET" x-data="schedulePriceSimulator"
            @submit.prevent="simulate($el)" @change="hours = []"
            class="space-y-3 rounded-xl border border-bq-border bg-bq-surface p-4">
            <h2 class="font-semibold">Simulator harga</h2>
            <div class="flex flex-wrap items-end gap-3">
                <label class="grid gap-1 text-sm">Layanan
        <select name="idlayanan" class="rounded border border-bq-border bg-bq-surface p-2" required>
            @foreach ($daftarlayanan as $service)
                <option value="{{ $service->id }}">{{ $service->namalayanan }}</option>
            @endforeach
        </select>
    </label>
                <label class="grid gap-1 text-sm">Tanggal
        <input type="date" name="tanggal" required class="rounded border border-bq-border bg-bq-surface p-2">
    </label>
                <button :disabled="busy" class="rounded-lg bg-bq-primary px-4 py-2 text-sm font-semibold text-white">Lihat harga per jam</button>
            </div>
            <p x-show="error" x-cloak x-text="error" role="alert" class="text-sm"></p>
            <ul class="grid gap-2 text-sm sm:grid-cols-3" aria-live="polite">
                <template x-for="hour in hours" :key="hour.time">
                    <li class="rounded border border-bq-border p-2 tabular-nums" x-text="hour.time + ' · ' + rupiah.format(hour.price)"></li>
                </template>
            </ul>
        </form>
    @endif
</div>
