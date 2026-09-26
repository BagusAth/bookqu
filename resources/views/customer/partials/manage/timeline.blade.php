                {{-- Timeline Riwayat Aktivitas --}}
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-xs">
                    <h3 class="text-base font-bold text-slate-900 pb-4 border-b border-slate-100 flex items-center gap-2">
                        <svg class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Riwayat &amp; Status Reservasi
                    </h3>
                    <div class="mt-4 space-y-4">
                        @forelse($booking->logs as $log)
                            @php
                                $badgeStyle = match($log->event) {
                                    'created'         => ['bg' => 'bg-indigo-50 text-indigo-600 border-indigo-200', 'title' => 'Reservasi Dibuat'],
                                    'payment_pending' => ['bg' => 'bg-amber-50 text-amber-600 border-amber-200', 'title' => 'Menunggu Pembayaran'],
                                    'payment_success' => ['bg' => 'bg-emerald-50 text-emerald-600 border-emerald-200', 'title' => 'Pembayaran Terkonfirmasi'],
                                    'payment_failed'  => ['bg' => 'bg-rose-50 text-rose-600 border-rose-200', 'title' => 'Pembayaran Gagal'],
                                    'cancelled'       => ['bg' => 'bg-rose-50 text-rose-600 border-rose-200', 'title' => 'Booking Dibatalkan'],
                                    'rescheduled'     => ['bg' => 'bg-indigo-50 text-[#4F46E5] border-[#C7D2FE]', 'title' => 'Jadwal Diubah'],
                                    'viewed'          => ['bg' => 'bg-slate-100 text-slate-600 border-slate-200', 'title' => 'Detail Dilihat'],
                                    'reviewed'        => ['bg' => 'bg-amber-50 text-amber-600 border-amber-200', 'title' => 'Ulasan Diberikan'],
                                    default           => ['bg' => 'bg-slate-100 text-slate-600 border-slate-200', 'title' => ucfirst($log->event)],
                                };
                            @endphp
                            <div class="flex items-start gap-3 text-xs sm:text-sm">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border {{ $badgeStyle['bg'] }} font-bold text-xs">
                                    •
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <p class="font-bold text-slate-900">{{ $badgeStyle['title'] }}</p>
                                        <span class="text-[11px] text-slate-400">{{ $log->created_at->translatedFormat('d M Y, H:i') }}</span>
                                    </div>
                                    <p class="text-xs text-slate-600 mt-0.5">{{ $log->note }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-slate-400 text-center py-4">Belum ada riwayat aktivitas tercatat.</p>
                        @endforelse
                    </div>
                </div>
