@extends('customer.layouts.booking-shell')

@section('title', 'Akses reservasi')
@section('minimal', '1')

@section('head')
    <meta name="robots" content="noindex,nofollow">
@endsection

{{-- Opening an access link (design A §7.9): the business is not known yet, so the page shows BookQu. --}}
@section('content')
    <section class="sf-card mx-auto mt-6 max-w-md px-6 py-8 text-center">
        {{-- Text mark, not an image: this page loads no resources while it holds the credential. --}}
        <span class="mx-auto grid size-12 place-items-center rounded-sf-card-small bg-sf-text text-base font-bold text-sf-surface" aria-label="BookQu" role="img">BQ</span>
        <h1 class="mt-4 text-lg font-bold">Akses reservasi</h1>
        <div id="access-progress" class="mt-4" aria-hidden="true">
            <span class="mx-auto block size-8 animate-spin rounded-full border-[3px] border-sf-divider border-t-sf-primary motion-reduce:hidden"></span>
        </div>
        <p id="message" class="mt-3 text-sm text-sf-muted" role="status">Membuka reservasi Anda…</p>
        <div id="access-recovery" hidden class="mt-4 space-y-4">
            <p class="text-sm text-sf-muted">Buka kembali tautan di email konfirmasi atau hubungi bisnis tempat Anda memesan.</p>
            <a href="/" class="sf-btn">Kembali ke BookQu</a>
        </div>
        <noscript><p class="mt-4 text-sm">Aktifkan JavaScript, lalu buka kembali tautan di email konfirmasi.</p></noscript>
    </section>
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
