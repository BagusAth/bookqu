<div id="api" class="space-y-5">
    <h3 class="text-base font-semibold">API dan Webhook</h3>
    <x-owner.field-error name="delivery" />
    @isset($credential)
        <section class="bq-panel p-4" role="status">
            <h2 class="font-semibold">{{ $label }}</h2>
            <p class="text-sm text-bq-text-muted">Salin sekarang. Nilai ini hanya tampil sekali.</p>
            <div x-data class="space-y-2">
                <code x-ref="credential" class="block break-all pt-2 text-sm">{{ $credential }}</code>
                <button type="button" class="text-sm font-semibold text-bq-primary" @click="
                    try {
                        await navigator.clipboard.writeText($refs.credential.textContent.trim());
                        $dispatch('toast', { type: 'success', message: 'Kredensial disalin.' });
                    } catch {
                        $dispatch('toast', { type: 'error', message: 'Salin kredensial secara manual.' });
                    }
                ">Salin kredensial</button>
            </div>
        </section>
    @endisset

    <section class="space-y-3 bq-panel p-4">
        <h2 class="font-semibold">Kunci API</h2>
        <form method="POST" action="{{ route('owner.api.keys.store') }}" class="flex flex-wrap items-end gap-2">
            @csrf
            <label class="grid gap-1 text-sm">Nama kunci
                <input name="name" required maxlength="100" class="bq-input">
            </label>
            <button class="bq-btn bq-btn-primary">Buat kunci</button>
        </form>
        <ul class="divide-y divide-bq-border text-sm">
            @foreach ($keys as $key)
                <li class="flex items-center justify-between gap-2 py-2">
                    <span>{{ $key->name }} <code>{{ $key->prefix }}…</code></span>
                    <form method="POST" action="{{ route('owner.api.keys.revoke', $key->id) }}" id="revoke-api-key-{{ $key->id }}" x-data>
                        @csrf
                        @method('DELETE')
                        <button type="button" class="font-semibold text-bq-danger" @click="$dispatch('open-confirm', { title: 'Cabut kunci API?', message: 'Aplikasi yang memakai kunci ini akan kehilangan akses.', formId: $el.form.id, tone: 'danger' })">Cabut</button>
                    </form>
                </li>
            @endforeach
        </ul>
    </section>

    <section class="space-y-3 bq-panel p-4">
        <h2 class="font-semibold">Endpoint webhook</h2>
        <form method="POST" action="{{ route('owner.api.webhooks.store') }}" class="flex flex-wrap items-end gap-2">
            @csrf
            <label class="grid gap-1 text-sm">Nama endpoint
                <input name="name" required maxlength="100" class="bq-input">
            </label>
            <label class="grid min-w-0 flex-1 gap-1 text-sm">URL HTTPS
                <input name="url" type="url" required maxlength="2048" class="bq-input">
            </label>
            <button class="bq-btn bq-btn-primary">Tambah endpoint</button>
        </form>
        <ul class="divide-y divide-bq-border text-sm">
            @foreach ($endpoints as $endpoint)
                <li class="flex flex-wrap items-center justify-between gap-2 py-2">
                    <span class="min-w-0 break-all">{{ $endpoint->name }} · {{ $endpoint->url }}</span>
                    <form method="POST" action="{{ route('owner.api.webhooks.test', $endpoint->id) }}">
                        @csrf
                        <button class="font-semibold text-bq-primary">Kirim uji</button>
                    </form>
                    <form method="POST" action="{{ route('owner.api.webhooks.destroy', $endpoint->id) }}" id="delete-webhook-{{ $endpoint->id }}" x-data>
                        @csrf
                        @method('DELETE')
                        <button type="button" class="font-semibold text-bq-danger" @click="$dispatch('open-confirm', { title: 'Hapus endpoint?', message: 'Pengiriman ke alamat ini akan berhenti.', formId: $el.form.id, tone: 'danger' })">Hapus</button>
                    </form>
                </li>
            @endforeach
        </ul>
    </section>

    <section class="space-y-2 bq-panel p-4">
        <h2 class="font-semibold">Pengiriman terakhir</h2>
        <ul class="divide-y divide-bq-border text-sm">
            @forelse ($deliveries as $delivery)
                <li class="break-words py-2">
                    <span class="font-semibold">{{ $delivery->event_name }}</span>
                    <span>· {{ $delivery->status_label }} · {{ $delivery->attempts }} percobaan</span>
                    @if ($delivery->http_status !== null)<span>· HTTP {{ $delivery->http_status }} · {{ $delivery->elapsed_ms }} ms</span>@endif
                    <code class="block break-all text-xs text-bq-text-muted">{{ $delivery->event_id }}</code>
                    @if ($delivery->can_redeliver)
                        <form class="mt-1" method="POST" action="{{ route('owner.api.webhooks.redeliver', $delivery->id) }}">
                            @csrf
                            <button class="font-semibold text-bq-primary">Kirim ulang</button>
                        </form>
                    @elseif ($delivery->status !== 'pending')
                        <span class="text-xs text-bq-text-muted">{{ $delivery->endpoint_id ? 'Data untuk kirim ulang sudah tidak tersedia.' : 'Endpoint sudah dihapus.' }}</span>
                    @endif
                </li>
            @empty
                <li class="text-bq-text-muted">Belum ada pengiriman.</li>
            @endforelse
        </ul>
    </section>
</div>
