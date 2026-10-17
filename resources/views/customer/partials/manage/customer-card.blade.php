                {{-- Detail Pelanggan & Booking --}}
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-xs">
                    <h3 class="text-base font-bold text-slate-900 pb-4 border-b border-slate-100 flex items-center gap-2">
                        <svg class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        Informasi Pemesan
                    </h3>
                    <div class="divide-y divide-slate-100 text-xs sm:text-sm">
                        <div class="py-3 flex justify-between items-center gap-4">
                            <span class="text-slate-500">Nama Pelanggan</span>
                            <span class="font-semibold text-slate-900">{{ $booking->namapelanggan }}</span>
                        </div>
                        <div class="py-3 flex justify-between items-center gap-4">
                            <span class="text-slate-500">Email Konfirmasi</span>
                            <span class="font-semibold text-slate-900">{{ $booking->email }}</span>
                        </div>
                        <div class="py-3 flex justify-between items-center gap-4">
                            <span class="text-slate-500">Nomor WhatsApp</span>
                            <span class="font-semibold text-slate-900">{{ $booking->nomorhp }}</span>
                        </div>
                        @if($booking->catatan)
                            <div class="py-3 flex flex-col sm:flex-row sm:justify-between items-start gap-1 sm:gap-4">
                                <span class="text-slate-500">Catatan Khusus</span>
                                <span class="font-medium text-slate-800 sm:text-right max-w-sm">{{ $booking->catatan }}</span>
                            </div>
                        @endif
                    </div>
                </div>
