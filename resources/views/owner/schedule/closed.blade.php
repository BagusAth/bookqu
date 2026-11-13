<div class="space-y-4">
    <form method="POST" action="{{ route('owner.schedule.availability', ['tab' => 'closed']) }}" class="flex flex-wrap items-end gap-3 rounded-xl border border-bq-border bg-bq-surface p-4">
        @csrf
        <input type="hidden" name="weekend_price_type" value="{{ $tenant->weekend_price_type ?? 'none' }}">
        <input type="hidden" name="weekend_price_value" value="{{ $tenant->weekend_price_value }}">
        <label class="grid gap-1 text-sm">Tanggal tutup
            <input class="rounded border border-bq-border bg-bq-surface p-2" type="date" name="tanggal_block" value="{{ old('tanggal_block') }}" required>
        </label>
        <label class="grid gap-1 text-sm">Alasan
            <input class="rounded border border-bq-border bg-bq-surface p-2" name="alasan" value="{{ old('alasan') }}" maxlength="200">
        </label>
        <button class="rounded-lg bg-bq-primary px-4 py-2 text-sm font-semibold text-white">Tutup tanggal</button>
    </form>
    @if ($conflicts = session('blocked_date_conflicts'))
        <section id="blocked-date-conflicts" class="rounded-xl border border-bq-border bg-bq-surface p-4" aria-label="Booking aktif">
            <p class="font-semibold">Tangani booking aktif sebelum menutup tanggal ini.</p>
            <ul class="mt-2 space-y-1 text-sm">
                @foreach ($conflicts['bookings'] as $conflict)
                    <li><a class="text-bq-primary underline" href="{{ route('owner.bookings', ['katakunci' => $conflict['booking_code']]) }}">{{ $conflict['booking_code'] }}</a> · {{ $conflict['jam'] }} · {{ $conflict['layanan'] }} · {{ $conflict['namapelanggan'] }}</li>
                @endforeach
            </ul>
        </section>
    @endif
    @forelse ($blockedDates as $date)
        <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-bq-border bg-bq-surface p-4">
            <span>{{ $date->tanggal->format('d/m/Y') }} {{ $date->alasan }}</span>
            <form method="POST" action="{{ route('owner.schedule.blocked-dates.delete', ['blockedDate' => $date, 'tab' => 'closed']) }}">
                @csrf
                @method('DELETE')
                <button class="text-sm font-semibold text-bq-primary">Buka kembali tanggal</button>
            </form>
        </div>
    @empty
        <x-owner.empty-state title="Belum ada tanggal tutup" description="Tambahkan hari libur atau tanggal saat bisnis tidak menerima booking." />
    @endforelse
</div>
