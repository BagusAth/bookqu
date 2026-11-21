{{-- Review (design A §7.6): shown only for completed bookings; 44 px stars. --}}
@if ($booking->review)
    <section aria-labelledby="judul-ulasan" class="sf-card p-[18px] md:p-6">
        <h2 id="judul-ulasan" class="text-[17px] font-bold">Ulasan Anda</h2>
        <p class="mt-2 flex items-center gap-1 text-sf-primary-text" aria-label="{{ $booking->review->rating }} dari 5 bintang">
            @foreach (range(1, 5) as $star)
                <x-customer.icon name="star" :size="20" :class="$star <= $booking->review->rating ? 'fill-current' : 'text-sf-line'" />
            @endforeach
            <span class="sf-num ml-1 text-sm text-sf-muted">{{ $booking->review->rating }}/5</span>
        </p>
        @if ($booking->review->komentar)
            <p class="mt-2 text-sm">{{ $booking->review->komentar }}</p>
        @endif
        @if ($booking->review->balasan)
            <div class="mt-3 rounded-sf-card-small bg-sf-tint px-4 py-3">
                <p class="text-[13px] font-semibold">Tanggapan dari {{ $tenant->namabisnis }}</p>
                <p class="mt-1 text-sm">{{ $booking->review->balasan }}</p>
            </div>
        @endif
    </section>
@else
    <section aria-labelledby="judul-ulasan-baru" class="sf-card p-[18px] md:p-6">
        <h2 id="judul-ulasan-baru" class="text-[17px] font-bold">Berikan ulasan layanan</h2>
        <form method="POST" action="{{ route('booking.manage.review', ['booking_code' => $booking->booking_code]) }}" class="mt-3 space-y-4"
            x-data="{ rating: {{ (int) old('rating', 0) }} }">
            @csrf
            <fieldset>
                <legend class="text-[13px] font-semibold">Penilaian</legend>
                <div class="mt-2 flex gap-1">
                    @foreach (range(1, 5) as $rating)
                        <label class="grid size-11 cursor-pointer place-items-center rounded-sf-control text-sf-line has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-sf-text"
                            x-bind:class="rating >= {{ $rating }} ? 'text-sf-primary-text' : 'text-sf-line'">
                            <input type="radio" name="rating" value="{{ $rating }}" aria-label="{{ $rating }} bintang" required
                                @checked((int) old('rating') === $rating) x-model.number="rating" class="sr-only">
                            <x-customer.icon name="star" :size="28" class="fill-current" />
                        </label>
                    @endforeach
                </div>
            </fieldset>
            <div>
                <label for="komentar" class="text-[13px] font-semibold">Komentar (opsional)</label>
                <textarea id="komentar" name="komentar" rows="3" maxlength="1000" class="sf-area mt-1.5">{{ old('komentar') }}</textarea>
            </div>
            <button type="submit" class="sf-btn">Kirim ulasan</button>
        </form>
    </section>
@endif
