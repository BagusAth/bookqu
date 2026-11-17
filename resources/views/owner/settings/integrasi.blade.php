@isset($configured)
    <x-ui.form-row title="Google Calendar" description="Booking terkonfirmasi dan perubahan jadwal dikirim ke kalender utama Google Anda.">
        @error('calendar')<p class="text-sm text-bq-danger" role="alert">{{ $message }}</p>@enderror
        @if ($connected)
            <p class="text-sm font-semibold text-bq-success">Terhubung</p>
            <div class="flex flex-wrap gap-3">
                <form method="POST" action="{{ route('owner.settings.integrations.calendar.test') }}">
                    @csrf
                    <button class="bq-btn bq-btn-secondary">Tes koneksi</button>
                </form>
                <form method="POST" action="{{ route('owner.settings.integrations.calendar.disconnect') }}" id="disconnect-calendar" x-data>
                    @csrf
                    @method('DELETE')
                    <button type="button" class="bq-btn bq-btn-danger" @click="$dispatch('open-confirm', { title: 'Putuskan Google Calendar?', message: 'Booking baru tidak akan disinkronkan ke Google Calendar.', formId: $el.form.id, tone: 'danger' })">Putuskan koneksi</button>
                </form>
            </div>
        @elseif ($configured)
            <form method="POST" action="{{ route('owner.settings.integrations.calendar.connect') }}">
                @csrf
                <button class="bq-btn bq-btn-primary">Hubungkan Google Calendar</button>
            </form>
        @else
            <p class="text-sm text-bq-text-muted">Google Calendar belum dikonfigurasi.</p>
        @endif
    </x-ui.form-row>

    <form method="POST" action="{{ route('owner.settings.integrations.tracking') }}">
        @csrf
        @method('PUT')
        <x-ui.form-row title="Pengukuran halaman booking">
        <div class="grid gap-3 sm:grid-cols-2">
            <label class="grid gap-1 text-sm">Google Analytics Measurement ID
                <input name="google_analytics_id" maxlength="24" pattern="G-[A-Z0-9]+" value="{{ old('google_analytics_id', $googleAnalytics) }}" class="bq-input" placeholder="G-…">
                @error('google_analytics_id')<span class="text-xs text-bq-danger">{{ $message }}</span>@enderror
            </label>
            <label class="grid gap-1 text-sm">Meta Pixel ID
                <input name="meta_pixel_id" maxlength="24" inputmode="numeric" pattern="[0-9]{8,24}" value="{{ old('meta_pixel_id', $metaPixel) }}" class="bq-input">
                @error('meta_pixel_id')<span class="text-xs text-bq-danger">{{ $message }}</span>@enderror
            </label>
        </div>
        </x-ui.form-row>
        <x-ui.savebar class="!static flex-wrap">
            <x-ui.button variant="ghost" :href="route('owner.settings.business', ['tab' => 'integrasi'])">Batal</x-ui.button>
            <x-ui.button type="submit" variant="primary">Simpan</x-ui.button>
        </x-ui.savebar>
    </form>
    @endisset
    <x-ui.form-row title="Dikelola BookQu" description="Tidak perlu diatur.">
        <x-ui.kv :items="['Pembayaran' => 'Pembayaran online pelanggan', 'Email' => 'Bukti booking dan pengingat']" />
    </x-ui.form-row>

    @isset($keys)
        @include('owner.settings.api')
    @endisset
