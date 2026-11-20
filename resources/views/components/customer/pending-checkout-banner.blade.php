@props(['pending'])

<x-ui.banner title="Anda masih memiliki pembayaran yang belum selesai" tone="warning" {{ $attributes }}>
    <p class="mt-1 text-bq-small">
        {{ $pending['service'] }} · {{ $pending['date'] }} · <span class="bq-num">{{ $pending['times'] }} WIB</span>
    </p>
    <p class="mt-1 text-bq-small">
        Jadwal ditahan sampai pukul <strong class="bq-num">{{ $pending['deadline'] }} WIB</strong>.
        Untuk memilih jadwal lain, batalkan dulu pesanan ini dari halaman pembayaran.
    </p>
    <x-slot:action>
        <x-ui.button :href="$pending['url']" class="min-h-11">Lanjutkan pembayaran</x-ui.button>
    </x-slot:action>
</x-ui.banner>
