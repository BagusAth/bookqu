{{-- Session expiry guard (BQ-112): an owner fetch that gets 419 or 401 opens this dialog instead of a generic failure. --}}
<script data-session-guard>
    (function () {
        if (window.bookquSessionGuardInstalled || typeof window.fetch !== 'function') {
            return;
        }
        window.bookquSessionGuardInstalled = true;

        const originalFetch = window.fetch.bind(window);
        const isSameOrigin = function (input) {
            try {
                const url = new URL(input instanceof Request ? input.url : String(input), window.location.href);
                return url.origin === window.location.origin;
            } catch (error) {
                return false;
            }
        };

        window.fetch = function (input, init) {
            return originalFetch(input, init).then(function (response) {
                if ((response.status === 419 || response.status === 401) && isSameOrigin(input)) {
                    window.dispatchEvent(new CustomEvent('session-expired'));
                }

                return response;
            });
        };
    })();
</script>

<div
    id="session-expired-dialog"
    x-data="{ buka: false }"
    x-on:session-expired.window="buka = true"
    x-show="buka"
    x-cloak
    style="display: none;"
    class="fixed inset-0 z-[130] flex items-center justify-center bg-[#231a3d]/50 p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="session-expired-title"
>
    <div class="w-full max-w-sm rounded-2xl border border-bq-border bg-white p-6 shadow-2xl">
        <h2 id="session-expired-title" class="text-base font-bold text-[#231a3d]">Sesi Anda telah berakhir</h2>
        <p class="mt-2 text-xs leading-relaxed text-[#6e6584]">
            Perubahan terakhir belum tersimpan. Muat ulang halaman untuk melanjutkan, atau masuk lagi bila diminta.
        </p>
        <div class="mt-5 flex flex-col-reverse gap-2.5 sm:flex-row sm:justify-end">
            <a href="{{ route('login') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-center text-xs font-semibold text-slate-700 transition hover:bg-slate-50" id="btn-session-expired-login">
                Masuk lagi
            </a>
            <button type="button" @click="window.location.reload()" class="rounded-xl bg-bq-primary px-4 py-2.5 text-xs font-bold text-white transition hover:bg-bq-primary-hover" id="btn-session-expired-reload">
                Muat ulang halaman
            </button>
        </div>
    </div>
</div>
