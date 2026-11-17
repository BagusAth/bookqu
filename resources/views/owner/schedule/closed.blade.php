<div class="space-y-4">
    <form method="POST" action="{{ route('owner.schedule.availability', ['tab' => 'closed']) }}" class="flex flex-wrap items-end gap-3 bq-panel p-4">
        @csrf
        <label class="grid gap-1 text-sm">Tanggal tutup
            <input class="bq-input" type="date" name="tanggal_block" value="{{ old('tanggal_block') }}" required>
            <x-owner.field-error name="tanggal_block" />
        </label>
        <label class="grid gap-1 text-sm">Alasan
            <input class="bq-input" name="alasan" value="{{ old('alasan') }}" maxlength="200">
        </label>
        <button class="bq-btn bq-btn-primary">Tutup tanggal</button>
    </form>
    @if ($conflicts = session('blocked_date_conflicts'))
        <section id="blocked-date-conflicts" class="bq-panel p-4" aria-label="Booking aktif">
            <p class="font-semibold">Tangani booking aktif sebelum menutup tanggal ini.</p>
            <ul class="mt-2 space-y-1 text-sm">
                @foreach ($conflicts['bookings'] as $conflict)
                    <li><a class="bq-link" href="{{ route('owner.bookings', ['katakunci' => $conflict['booking_code']]) }}">{{ $conflict['booking_code'] }}</a> · {{ $conflict['jam'] }} · {{ $conflict['layanan'] }} · {{ $conflict['namapelanggan'] }}</li>
                @endforeach
            </ul>
        </section>
    @endif
    @if ($blockedDates->isNotEmpty())
        <x-ui.table :columns="['Tanggal', 'Alasan']" label="Tanggal tutup" actions>
            @foreach ($blockedDates as $date)
                <div role="row" class="bq-table-row bq-table-double">
                    <div role="cell" class="bq-table-cell bq-table-main">{{ $date->tanggal->locale('id')->translatedFormat('j M Y') }}</div>
                    <div role="cell" class="bq-table-cell bq-table-sub truncate text-bq-small text-bq-text-muted" title="{{ $date->alasan }}">{{ $date->alasan ?: '—' }}</div>
                    <div role="cell" class="bq-table-action">
                        <form method="POST" id="reopen-date-{{ $date->id }}" x-data action="{{ route('owner.schedule.blocked-dates.delete', ['blockedDate' => $date, 'tab' => 'closed']) }}">
                            @csrf
                            @method('DELETE')
                            <button type="button" class="text-sm font-semibold text-bq-primary"
                                @click="$dispatch('open-confirm', { title: 'Buka kembali tanggal?', message: 'Slot yang tersedia pada tanggal ini dapat dipesan kembali.', formId: $el.form.id, tone: 'primary', confirmText: 'Buka tanggal' })">Buka kembali tanggal</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </x-ui.table>
        <p class="text-bq-small text-bq-text-muted">Libur mingguan diatur di tab Jam buka.</p>
    @else
        <x-owner.empty-state title="Belum ada tanggal tutup" description="Tambahkan hari libur atau tanggal saat bisnis tidak menerima booking." />
    @endif
</div>
