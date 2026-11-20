@if ($booking->review)
    <x-ui.panel title="Ulasan Anda">
        <p class="bq-num text-bq-body">{{ $booking->review->rating }}/5</p>
        @if ($booking->review->komentar)
            <p class="mt-2 text-bq-body">{{ $booking->review->komentar }}</p>
        @endif
        @if ($booking->review->balasan)
            <div class="mt-3 border-t border-bq-border pt-3">
                <p class="text-bq-label">Tanggapan dari {{ $tenant->namabisnis }}</p>
                <p class="text-bq-body">{{ $booking->review->balasan }}</p>
            </div>
        @endif
    </x-ui.panel>
@else
    <x-ui.panel title="Berikan ulasan layanan">
        <form method="POST" action="{{ route('booking.manage.review', ['booking_code' => $booking->booking_code]) }}" class="space-y-4">
            @csrf
            <fieldset class="space-y-2">
                <legend class="text-bq-label">Penilaian</legend>
                <div class="flex flex-wrap gap-2">
                    @foreach (range(1, 5) as $rating)
                        <label class="bq-btn bq-btn-secondary min-h-11 gap-2">
                            <input type="radio" name="rating" value="{{ $rating }}" aria-label="{{ $rating }} bintang" required @checked((int) old('rating') === $rating)
                                class="accent-bq-primary">
                            <span>{{ $rating }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
            <x-ui.field label="Komentar (opsional)" for="komentar" name="komentar">
                <textarea id="komentar" name="komentar" rows="3" maxlength="1000" class="bq-input w-full">{{ old('komentar') }}</textarea>
            </x-ui.field>
            <div class="fixed inset-x-0 bottom-0 z-30 border-t border-bq-border bg-bq-surface p-4 sm:static sm:border-0 sm:p-0">
                <x-ui.button type="submit" variant="primary" class="min-h-11 w-full sm:w-auto">Kirim ulasan</x-ui.button>
            </div>
        </form>
    </x-ui.panel>
@endif
