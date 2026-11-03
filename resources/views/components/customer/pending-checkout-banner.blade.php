@props(['pending'])

{{-- The customer's own unpaid checkout: its slots stay held until the payment deadline. --}}
<div {{ $attributes->merge(['class' => 'rounded-2xl border border-amber-200 bg-amber-50 p-4 sm:p-5']) }} role="status">
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4">
        <div class="flex-1 min-w-0">
            <p class="text-sm font-bold text-amber-900">Anda masih memiliki pembayaran yang belum selesai</p>
            <p class="mt-1 text-xs text-amber-800 leading-relaxed">
                {{ $pending['service'] }} · {{ $pending['date'] }} · {{ $pending['times'] }} WIB.
                Jadwal ini ditahan untuk Anda sampai pukul <strong>{{ $pending['deadline'] }} WIB</strong>.
                Untuk memilih jadwal lain, batalkan dulu pesanan ini dari halaman pembayaran.
            </p>
        </div>
        <a href="{{ $pending['url'] }}" class="inline-flex shrink-0 items-center justify-center rounded-xl bg-amber-600 px-4 py-2.5 text-xs sm:text-sm font-bold text-white shadow-sm hover:bg-amber-700 transition-colors">
            Lanjutkan Pembayaran
        </a>
    </div>
</div>
