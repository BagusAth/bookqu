{{-- Booking history (design A §7.6). --}}
<section aria-labelledby="judul-riwayat" class="sf-card p-[18px] md:p-6">
    <h2 id="judul-riwayat" class="text-[15px] font-bold">Riwayat reservasi</h2>
    @if ($history === [])
        <p class="mt-2 text-sm text-sf-muted">Belum ada perubahan yang tercatat.</p>
    @else
        <ol class="mt-3 space-y-3 border-l-2 border-sf-divider pl-4">
            @foreach ($history as $entry)
                <li class="relative">
                    <span class="absolute -left-[23px] top-1.5 size-3 rounded-full border-2 border-sf-surface bg-sf-primary" aria-hidden="true"></span>
                    <p class="text-sm font-semibold">{{ $entry['label'] }}</p>
                    <p class="sf-num text-[13px] text-sf-muted">{{ $entry['at'] }} WIB</p>
                </li>
            @endforeach
        </ol>
    @endif
</section>
