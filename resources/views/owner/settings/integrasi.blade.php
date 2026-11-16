<div class="space-y-5">
    <h2 class="text-lg font-semibold">Integrasi</h2>


    @isset($configured)
    <section class="space-y-3 rounded-xl border border-bq-border bg-bq-surface p-4">
        <h2 class="font-semibold">Google Calendar</h2>
        <p class="text-sm text-bq-text-muted">Booking terkonfirmasi dan perubahan jadwal dikirim dari BookQu ke kalender utama Google Anda.</p>
        @error('calendar')<p class="text-sm text-bq-danger" role="alert">{{ $message }}</p>@enderror
        @if ($connected)
            <p class="text-sm font-semibold text-bq-success">Terhubung</p>
            <div class="flex flex-wrap gap-3">
                <form method="POST" action="{{ route('owner.settings.integrations.calendar.test') }}">
                    @csrf
                    <button class="rounded-lg border border-bq-border px-3 py-2 text-sm">Tes koneksi</button>
                </form>
                <form method="POST" action="{{ route('owner.settings.integrations.calendar.disconnect') }}" id="disconnect-calendar" x-data>
                    @csrf
                    @method('DELETE')
                    <button type="button" class="rounded-lg border border-bq-border px-3 py-2 text-sm text-bq-danger" @click="$dispatch('open-confirm', { title: 'Putuskan Google Calendar?', message: 'Booking baru tidak akan disinkronkan ke Google Calendar.', formId: $el.form.id, tone: 'danger' })">Putuskan koneksi</button>
                </form>
            </div>
        @elseif ($configured)
            <form method="POST" action="{{ route('owner.settings.integrations.calendar.connect') }}">
                @csrf
                <button class="rounded-lg bg-bq-primary px-4 py-2 text-sm font-semibold text-white">Hubungkan Google Calendar</button>
            </form>
        @else
            <p class="text-sm text-bq-text-muted">Google Calendar belum dikonfigurasi.</p>
        @endif
    </section>

    <form method="POST" action="{{ route('owner.settings.integrations.tracking') }}" class="space-y-4 rounded-xl border border-bq-border bg-bq-surface p-4">
        @csrf
        @method('PUT')
        <h2 class="font-semibold">Pengukuran halaman booking</h2>
        <div class="grid gap-3 sm:grid-cols-2">
            <label class="grid gap-1 text-sm">Google Analytics Measurement ID
                <input name="google_analytics_id" maxlength="24" pattern="G-[A-Z0-9]+" value="{{ old('google_analytics_id', $googleAnalytics) }}" class="rounded-lg border border-bq-border bg-bq-surface p-2" placeholder="G-…">
                @error('google_analytics_id')<span class="text-xs text-bq-danger">{{ $message }}</span>@enderror
            </label>
            <label class="grid gap-1 text-sm">Meta Pixel ID
                <input name="meta_pixel_id" maxlength="24" inputmode="numeric" pattern="[0-9]{8,24}" value="{{ old('meta_pixel_id', $metaPixel) }}" class="rounded-lg border border-bq-border bg-bq-surface p-2">
                @error('meta_pixel_id')<span class="text-xs text-bq-danger">{{ $message }}</span>@enderror
            </label>
        </div>
        <button class="rounded-lg bg-bq-primary px-4 py-2 text-sm font-semibold text-white">Simpan</button>
    </form>
    @endisset
    <p class="text-sm text-bq-text-muted">Pembayaran dan email dikelola BookQu.</p>

    @isset($keys)
        @include('owner.settings.api')
    @endisset
</div>
