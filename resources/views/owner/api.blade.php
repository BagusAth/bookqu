@extends('layouts.owner-layout')

@section('title', 'API dan Webhook')

@section('content')
<div class="mx-auto max-w-4xl space-y-5">
    <x-owner.page-header title="API dan Webhook" purpose="Kelola akses baca dan pengiriman event booking." />
    @isset($credential)
        <section class="rounded-xl border border-bq-border bg-bq-surface p-4" role="status">
            <h2 class="font-semibold">{{ $label }}</h2>
            <p class="text-sm text-bq-text-muted">Salin sekarang. Nilai ini hanya tampil sekali.</p>
            <code class="block break-all pt-2 text-sm">{{ $credential }}</code>
        </section>
    @endisset

    <section class="space-y-3 rounded-xl border border-bq-border bg-bq-surface p-4">
        <h2 class="font-semibold">Kunci API</h2>
        <form method="POST" action="{{ route('owner.api.keys.store') }}" class="flex flex-wrap items-end gap-2">
            @csrf
            <label class="grid gap-1 text-sm">Nama kunci
                <input name="name" required maxlength="100" class="rounded border border-bq-border bg-bq-surface p-2">
            </label>
            <button class="rounded bg-bq-primary px-3 py-2 text-sm font-semibold text-white">Buat kunci</button>
        </form>
        <ul class="divide-y divide-bq-border text-sm">
            @foreach ($keys as $key)
                <li class="flex items-center justify-between gap-2 py-2">
                    <span>{{ $key->name }} <code>{{ $key->prefix }}…</code></span>
                    <form method="POST" action="{{ route('owner.api.keys.revoke', $key->id) }}">
                        @csrf
                        @method('DELETE')
                        <button class="font-semibold text-bq-danger">Cabut</button>
                    </form>
                </li>
            @endforeach
        </ul>
    </section>

    <section class="space-y-3 rounded-xl border border-bq-border bg-bq-surface p-4">
        <h2 class="font-semibold">Endpoint webhook</h2>
        <form method="POST" action="{{ route('owner.api.webhooks.store') }}" class="flex flex-wrap items-end gap-2">
            @csrf
            <label class="grid gap-1 text-sm">Nama endpoint
                <input name="name" required maxlength="100" class="rounded border border-bq-border bg-bq-surface p-2">
            </label>
            <label class="grid min-w-0 flex-1 gap-1 text-sm">URL HTTPS
                <input name="url" type="url" required maxlength="2048" class="rounded border border-bq-border bg-bq-surface p-2">
            </label>
            <button class="rounded bg-bq-primary px-3 py-2 text-sm font-semibold text-white">Tambah endpoint</button>
        </form>
        <ul class="divide-y divide-bq-border text-sm">
            @foreach ($endpoints as $endpoint)
                <li class="flex items-center justify-between gap-2 py-2">
                    <span class="min-w-0 break-all">{{ $endpoint->name }} · {{ $endpoint->url }}</span>
                    <form method="POST" action="{{ route('owner.api.webhooks.destroy', $endpoint->id) }}">
                        @csrf
                        @method('DELETE')
                        <button class="font-semibold text-bq-danger">Hapus</button>
                    </form>
                </li>
            @endforeach
        </ul>
    </section>

    <section class="space-y-2 rounded-xl border border-bq-border bg-bq-surface p-4">
        <h2 class="font-semibold">Pengiriman terakhir</h2>
        <ul class="divide-y divide-bq-border text-sm">
            @forelse ($deliveries as $delivery)
                <li class="break-words py-2">
                    <span class="font-semibold">{{ $delivery->event_name }}</span>
                    <span>· {{ $delivery->status }} · {{ $delivery->attempts }} percobaan</span>
                    @if ($delivery->http_status !== null)<span>· HTTP {{ $delivery->http_status }} · {{ $delivery->elapsed_ms }} ms</span>@endif
                    <code class="block break-all text-xs text-bq-text-muted">{{ $delivery->event_id }}</code>
                </li>
            @empty
                <li class="text-bq-text-muted">Belum ada pengiriman.</li>
            @endforelse
        </ul>
    </section>
</div>
@endsection
