<form method="POST" action="{{ route('owner.settings.notification-preferences') }}" class="space-y-4 rounded-xl border border-bq-border bg-bq-surface p-4">
    @csrf
    @method('PATCH')
    <h2 class="font-semibold">Notifikasi</h2>
    <p class="text-sm text-bq-text-muted">Notifikasi di aplikasi selalu aktif. Email dikirim ke {{ $accountUser?->email }}.</p>
    @foreach ($notificationEvents as $event => $label)
        <div class="flex items-center justify-between gap-3 border-t border-bq-border pt-2 text-sm">
            <input type="hidden" name="preferences[{{ $event }}]" value="0">
            <label class="flex w-full items-center justify-between gap-3">
                {{ $label }}
                <span class="flex items-center gap-2">Email <input type="checkbox" name="preferences[{{ $event }}]" value="1" @checked(old('preferences.' . $event, $notificationPreferences[$event] ?? true))></span>
            </label>
        </div>
    @endforeach
    <button class="rounded-lg bg-bq-primary px-4 py-2 text-sm font-semibold text-white">Simpan preferensi</button>
</form>
