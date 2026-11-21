<form method="POST" action="{{ route('owner.settings.notification-preferences') }}">
    @csrf
    @method('PATCH')
    <x-ui.form-row title="Per kejadian" description="Notifikasi di aplikasi selalu aktif. Email bisa dimatikan.">
        <x-owner.field-error name="preferences" />
        @foreach ($notificationEvents as $event => $label)
            @continue($event === 'weekly_summary')
            <div class="border-b border-bq-border pb-2">
                <input type="hidden" name="preferences[{{ $event }}]" value="0">
                <x-ui.switch :name="'preferences['.$event.']'" value="1" :label="$label"
                    :checked="(bool) old('preferences.'.$event, $notificationPreferences[$event] ?? true)" />
                <x-owner.field-error :name="'preferences.'.$event" />
            </div>
        @endforeach
        <label class="grid gap-1 text-sm">Kirim email ke
            <x-ui.input :value="$accountUser?->email" readonly />
        </label>
    </x-ui.form-row>
    @isset($notificationEvents['weekly_summary'])
        <x-ui.form-row title="Ringkasan mingguan" description="Setiap Senin pagi.">
            <input type="hidden" name="preferences[weekly_summary]" value="0">
            <x-ui.switch name="preferences[weekly_summary]" value="1" label="Kirim ringkasan mingguan"
                :checked="(bool) old('preferences.weekly_summary', $notificationPreferences['weekly_summary'] ?? true)" />
            <x-owner.field-error name="preferences.weekly_summary" />
        </x-ui.form-row>
    @endisset
    <x-ui.savebar class="!static flex-wrap">
        <x-ui.button variant="ghost" :href="route('owner.settings', ['tab' => 'notifications'])">Batal</x-ui.button>
        <x-ui.button type="submit" variant="primary">Simpan preferensi</x-ui.button>
    </x-ui.savebar>
</form>
