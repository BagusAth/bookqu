@extends('customer.layouts.booking-shell')

@section('title', 'Akses reservasi')
@section('minimal', '1')

@section('head')
    <meta name="robots" content="noindex,nofollow">
@endsection

@section('content')
    <x-ui.panel class="mx-auto max-w-md">
        <h1 class="text-bq-section text-bq-ink">Akses reservasi</h1>
        <p id="message" class="mt-2 text-bq-body text-bq-text-muted" role="status">Membuka reservasi Anda…</p>
        <progress id="access-progress" aria-label="Membuka reservasi" class="mt-4 h-2 w-full accent-bq-primary motion-reduce:hidden"></progress>
        <div id="access-recovery" hidden class="mt-4 space-y-4">
            <p class="text-bq-body text-bq-text-muted">Buka kembali tautan di email konfirmasi atau hubungi bisnis tempat Anda memesan.</p>
            <x-ui.button href="/" class="min-h-11">Kembali ke BookQu</x-ui.button>
        </div>
        <noscript><p class="mt-4 text-bq-body">Aktifkan JavaScript, lalu buka kembali tautan di email konfirmasi.</p></noscript>
    </x-ui.panel>
@endsection

@section('scripts')
    <script>
        (() => {
            const showRecovery = () => {
                document.getElementById('message').textContent = 'Tautan tidak berlaku atau sudah kedaluwarsa.';
                document.getElementById('access-progress').hidden = true;
                document.getElementById('access-recovery').hidden = false;
            };
            let credential = window.location.hash.slice(1);
            window.history.replaceState(null, '', window.location.pathname);
            if (!credential) {
                showRecovery();
                return;
            }
            fetch(@js(route('customer.access.store', [], false)), {
                method: 'POST',
                credentials: 'same-origin',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @js(csrf_token())},
                body: JSON.stringify({credential})
            }).then(response => {
                credential = '';
                if (!response.ok) throw new Error('unavailable');
                return response.json();
            }).then(data => window.location.replace(data.redirect)).catch(() => {
                credential = '';
                showRecovery();
            });
        })();
    </script>
@endsection
