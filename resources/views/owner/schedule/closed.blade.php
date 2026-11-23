{{-- BQ-162: Libur & tutup tab as in the Papan Jadwal prototype (#/jam-buka/libur): one table, a menu per date. --}}
<div class="grid gap-4">
    @if ($conflicts = session('blocked_date_conflicts'))
        <x-ui.banner tone="danger" id="blocked-date-conflicts"
            :title="count($conflicts['bookings']).' booking aktif pada tanggal ini'"
            description="Pindahkan atau batalkan semuanya dulu. Tanggal belum bisa ditutup.">
            <ul class="mt-2 space-y-1 text-bq-small">
                @foreach ($conflicts['bookings'] as $conflict)
                    <li><a class="bq-link" href="{{ route('owner.bookings', ['katakunci' => $conflict['booking_code']]) }}">{{ $conflict['booking_code'] }}</a> · {{ $conflict['jam'] }} · {{ $conflict['layanan'] }} · {{ $conflict['namapelanggan'] }}</li>
                @endforeach
            </ul>
        </x-ui.banner>
    @endif

    <section class="bq-panel min-w-0">
        @if ($blockedDates->isEmpty() && $weeklyClosed === [])
            <x-ui.empty title="Belum ada tanggal tutup" description="Tutup tanggal saat bisnis tidak menerima booking, misalnya hari libur nasional." icon="calendar">
                <x-slot:action>
                    <x-ui.button variant="primary" icon="plus" x-data x-on:click="$dispatch('open-dialog', { id: 'schedule-close-date-dialog', trigger: $el })">Tutup tanggal</x-ui.button>
                </x-slot:action>
            </x-ui.empty>
        @else
            <x-ui.table class="rounded-none border-0" :columns="['Tanggal', 'Alasan']" widths="minmax(200px, 1fr) minmax(0, 2fr)" label="Tanggal tutup" actions>
                @foreach ($weeklyClosed as $dayName)
                    <x-ui.table.row>
                        <x-ui.table.cell phone="main"><span class="font-semibold">{{ $dayName }}, setiap minggu</span></x-ui.table.cell>
                        <x-ui.table.cell phone="subtitle">Libur mingguan (dari jam buka)</x-ui.table.cell>
                        <x-slot:action></x-slot:action>
                    </x-ui.table.row>
                @endforeach
                @foreach ($blockedDates as $date)
                    <x-ui.table.row>
                        <x-ui.table.cell phone="main"><span class="font-semibold">{{ $date->tanggal->locale('id')->translatedFormat('j M Y') }}</span></x-ui.table.cell>
                        <x-ui.table.cell phone="subtitle" :title="$date->alasan"><span class="truncate">{{ $date->alasan ?: '—' }}</span></x-ui.table.cell>
                        <x-slot:action>
                            <form method="POST" id="reopen-date-{{ $date->id }}" x-data action="{{ route('owner.schedule.blocked-dates.delete', ['blockedDate' => $date, 'tab' => 'closed']) }}">
                                @csrf
                                @method('DELETE')
                                <x-ui.menu :id="'closed-date-'.$date->id" :label="'Tindakan '.$date->tanggal->format('d/m/Y')">
                                    <button type="button" role="menuitem" tabindex="-1"
                                        @click="$dispatch('open-confirm', { title: 'Buka lagi tanggal ini?', message: 'Slot yang tersedia pada tanggal ini dapat dipesan kembali.', formId: 'reopen-date-{{ $date->id }}', tone: 'primary', confirmText: 'Buka tanggal' })">Buka lagi tanggal ini</button>
                                </x-ui.menu>
                            </form>
                        </x-slot:action>
                    </x-ui.table.row>
                @endforeach
            </x-ui.table>
            <p class="bq-pager">Libur mingguan diatur di tab Jam buka.</p>
        @endif
    </section>
</div>

<x-owner.dialog id="schedule-close-date-dialog" title="Tutup tanggal" reopen-for="close-date">
    <form method="POST" action="{{ route('owner.schedule.availability', ['tab' => 'closed']) }}" class="grid gap-4" id="close-date-form">
        @csrf
        <input type="hidden" name="_form" value="close-date">
        <div class="grid gap-1.5">
            <label for="close-date" class="bq-label">Tanggal <span class="text-bq-danger" aria-hidden="true">*</span></label>
            <input id="close-date" class="bq-input w-full" type="date" name="tanggal_block" value="{{ old('tanggal_block') }}" required>
            <x-owner.field-error name="tanggal_block" />
        </div>
        <div class="grid gap-1.5">
            <label for="close-reason" class="bq-label">Alasan</label>
            <input id="close-reason" class="bq-input w-full" name="alasan" value="{{ old('alasan') }}" maxlength="200" placeholder="Misalnya: Acara keluarga">
            <p class="bq-hint">Pelanggan melihat alasan ini di halaman booking.</p>
        </div>
        <div class="flex justify-end gap-2">
            <x-ui.button x-on:click="$dispatch('close-dialog', { id: 'schedule-close-date-dialog' })">Batal</x-ui.button>
            <x-ui.button type="submit" variant="primary">Tutup tanggal</x-ui.button>
        </div>
    </form>
</x-owner.dialog>
