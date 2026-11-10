{{--
    Toast (BQ-129, owner-portal.md §9): the one place flash messages appear. Session flashes
    (`sukses`, `pesan`, `error`, the first validation error) show once here; scripts add toasts with
    window.dispatchEvent(new CustomEvent('toast', { detail: { type, message } })).
--}}
@php
    $initialToasts = array_values(array_filter([
        session('sukses') ? ['type' => 'success', 'message' => (string) session('sukses')] : null,
        session('pesan') ? ['type' => 'warning', 'message' => (string) session('pesan')] : null,
        session('error') ? ['type' => 'error', 'message' => (string) session('error')] : null,
        isset($errors) && $errors->any() ? ['type' => 'error', 'message' => (string) $errors->first()] : null,
    ]));
@endphp

<div
    x-data="ownerToasts(@js($initialToasts))"
    x-on:toast.window="add($event.detail.type, $event.detail.message)"
    aria-live="polite"
    aria-atomic="false"
    class="pointer-events-none fixed right-4 top-4 z-[120] flex w-[calc(100%-2rem)] max-w-sm flex-col gap-2"
>
    <template x-for="toast in toasts" x-bind:key="toast.id">
        <div
            role="status"
            class="pointer-events-auto flex items-start gap-3 rounded-xl border px-4 py-3 shadow-lg"
            x-bind:class="{
                'border-bq-success/30 bg-bq-success-bg text-bq-success': toast.type === 'success',
                'border-bq-danger/30 bg-bq-danger-bg text-bq-danger': toast.type === 'error',
                'border-bq-warning/30 bg-bq-warning-bg text-bq-warning': toast.type === 'warning',
            }"
        >
            <p class="flex-1 text-sm font-medium leading-snug" x-text="toast.message"></p>
            <button type="button" x-on:click="remove(toast.id)" aria-label="Tutup notifikasi" class="rounded p-0.5 opacity-70 hover:opacity-100">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </template>
</div>
