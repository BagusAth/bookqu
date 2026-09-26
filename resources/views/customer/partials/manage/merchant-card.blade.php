                {{-- Tenant Information Card --}}
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-xs space-y-4">
                    <h3 class="text-base font-bold text-slate-900 pb-3 border-b border-slate-100 flex items-center gap-2">
                        <svg class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        Lokasi &amp; Kontak Merchant
                    </h3>

                    <div class="space-y-3 text-xs sm:text-sm">
                        <div class="flex items-start gap-3">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600 mt-0.5">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <div>
                                <span class="font-bold text-slate-900 block">{{ $booking->tenant->namabisnis }}</span>
                                <span class="text-slate-500 block leading-relaxed">{{ $booking->tenant->alamat ?? 'Alamat belum diatur' }}</span>
                                @if($booking->tenant->alamat)
                                    <a
                                        href="https://www.google.com/maps/search/?api=1&query={{ urlencode($booking->tenant->alamat . ' ' . $booking->tenant->namabisnis) }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 hover:text-indigo-800 mt-1"
                                    >
                                        <span>Buka di Google Maps</span>
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    </a>
                                @endif
                            </div>
                        </div>

                        @if($booking->tenant->nomorhp)
                            <div class="flex items-center gap-3 pt-2 border-t border-slate-100">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                </div>
                                <div>
                                    <span class="text-xs text-slate-500 block">Nomor Telepon</span>
                                    <a href="tel:{{ $booking->tenant->nomorhp }}" class="font-bold text-slate-900 hover:text-indigo-600 transition-colors">
                                        {{ $booking->tenant->nomorhp }}
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Security Notice --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-4 text-xs text-slate-500 space-y-1.5">
                    <div class="flex items-center gap-1.5 font-bold text-slate-700">
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <span>Tautan Akses Rahasia</span>
                    </div>
                    <p class="leading-relaxed">
                        Halaman ini memuat akses langsung untuk mengelola booking Anda tanpa login. Simpan tautan ini atau jangan bagikan kepada pihak yang tidak berwenang.
                    </p>
                </div>

