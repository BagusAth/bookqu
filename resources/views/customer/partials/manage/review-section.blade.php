                {{-- Review Section for Completed Bookings --}}
                @if($booking->status === 'completed')
                    @if($booking->review)
                        <div class="rounded-3xl border border-indigo-100 bg-gradient-to-b from-white to-indigo-50/20 p-6 shadow-xs space-y-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                                    <span class="flex h-7 w-7 items-center justify-center rounded-xl bg-amber-100 text-amber-600 font-bold text-sm">★</span>
                                    Ulasan Anda
                                </h3>
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-bold text-emerald-700 border border-emerald-200">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    Terkirim
                                </span>
                            </div>
                            <div class="rounded-2xl bg-white border border-slate-200/80 p-4 shadow-2xs space-y-2.5">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-1 text-amber-400">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <svg class="h-5 w-5 {{ $i <= $booking->review->rating ? 'fill-current' : 'text-slate-200 fill-current' }}" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                        @endfor
                                        <span class="ml-1.5 text-xs font-bold text-slate-900">{{ $booking->review->rating }}.0</span>
                                    </div>
                                    <span class="text-xs text-slate-400">{{ $booking->review->created_at->translatedFormat('d M Y, H:i') }} WIB</span>
                                </div>
                                @if($booking->review->komentar)
                                    <p class="text-xs sm:text-sm text-slate-700 italic bg-slate-50 rounded-xl p-3 border border-slate-100">
                                        "{{ $booking->review->komentar }}"
                                    </p>
                                @endif
                                @if($booking->review->balasan)
                                    <div class="rounded-xl border border-indigo-100 bg-indigo-50/50 p-3.5 space-y-1 mt-3">
                                        <div class="flex items-center gap-1.5 text-xs font-bold text-indigo-700">
                                            <span>Tanggapan dari {{ $booking->tenant->namabisnis }}</span>
                                            @if($booking->review->dibalas_pada)
                                                <span class="font-normal text-slate-500">· {{ $booking->review->dibalas_pada->translatedFormat('d M Y') }}</span>
                                            @endif
                                        </div>
                                        <p class="text-xs text-slate-600 leading-relaxed">{{ $booking->review->balasan }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @else
                        {{-- Review Form --}}
                        <div class="rounded-3xl border border-indigo-100 bg-gradient-to-b from-white to-indigo-50/20 p-6 shadow-xs"
                             x-data="{
                                 rating: 0,
                                 hoverRating: 0,
                                 labels: {1: 'Sangat Kurang', 2: 'Kurang Memuaskan', 3: 'Cukup', 4: 'Puas', 5: 'Sangat Puas!'}
                             }">
                            <div class="flex items-center gap-2.5">
                                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-amber-100 text-amber-600 font-bold text-sm">★</span>
                                <div>
                                    <h3 class="text-base font-bold text-slate-900">Berikan Ulasan Layanan</h3>
                                    <p class="text-xs text-slate-500">Bagikan pengalaman Anda saat menggunakan layanan dari {{ $booking->tenant->namabisnis }}.</p>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('booking.manage.review', ['booking_code' => $booking->booking_code]) }}" class="mt-5 space-y-4">
                                @csrf
                                <input type="hidden" name="token" value="{{ $token }}">
                                <input type="hidden" name="rating" :value="rating">

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Penilaian Bintang <span class="text-rose-500">*</span></label>
                                    <div class="flex items-center gap-3">
                                        <div class="flex items-center gap-1">
                                            <template x-for="star in [1, 2, 3, 4, 5]" :key="star">
                                                <button
                                                    type="button"
                                                    @click="rating = star"
                                                    @mouseenter="hoverRating = star"
                                                    @mouseleave="hoverRating = 0"
                                                    class="p-1 focus:outline-none transition-transform hover:scale-115 active:scale-95 cursor-pointer"
                                                >
                                                    <svg
                                                        class="h-8 w-8 transition-colors"
                                                        :class="(hoverRating || rating) >= star ? 'text-amber-400 fill-amber-400' : 'text-slate-200 fill-slate-200 hover:text-amber-300'"
                                                        viewBox="0 0 20 20"
                                                    >
                                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                    </svg>
                                                </button>
                                            </template>
                                        </div>
                                        <span
                                            x-text="labels[hoverRating || rating] || 'Pilih rating bintang'"
                                            class="text-xs font-bold"
                                            :class="rating > 0 ? 'text-indigo-600' : 'text-slate-400'"
                                        ></span>
                                    </div>
                                </div>

                                <div>
                                    <label for="komentar" class="block text-xs font-bold text-slate-700 mb-1.5">Komentar &amp; Testimoni (Opsional)</label>
                                    <textarea
                                        id="komentar"
                                        name="komentar"
                                        rows="3"
                                        maxlength="1000"
                                        placeholder="Ceritakan kepuasan Anda terhadap staf, fasilitas, atau hasil layanan..."
                                        class="w-full rounded-2xl border border-slate-200 p-3 text-xs sm:text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-600 focus:outline-none focus:ring-3 focus:ring-indigo-600/15 transition"
                                    >{{ old('komentar') }}</textarea>
                                </div>

                                <div class="flex justify-end">
                                    <button
                                        type="submit"
                                        :disabled="rating === 0"
                                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-xs sm:text-sm font-bold text-white shadow-md shadow-indigo-600/25 transition-all hover:bg-indigo-700 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
                                    >
                                        <span>Kirim Ulasan</span>
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif
                @endif
