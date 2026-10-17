    {{-- ── Bookings Mobile Cards (Screen < sm) ── --}}
    <div class="block sm:hidden space-y-3" id="bookings-mobile-cards">
        @forelse ($daftarbooking as $booking)
            @php
                $paymentStatus = $booking->payment?->status ?? ($booking->status === 'paid' ? 'sukses' : 'pending');
                $staffName = $booking->layanan?->staff?->pluck('name')->join(', ');
                $resourceName = $booking->layanan?->resources?->pluck('name')->join(', ');
                $staffResourceDisplay = $staffName ?: ($resourceName ?: 'General Staff');
                $bookingData = [
                    'id' => $booking->id,
                    'code' => $booking->booking_code ?? ('BKQ-' . $booking->id),
                    'name' => $booking->namapelanggan,
                    'email' => $booking->email,
                    'phone' => $booking->nomorhp,
                    'service' => $booking->layanan->namalayanan ?? 'Standard Service',
                    'price' => $booking->layanan->harga ?? 0,
                    'formatted_price' => 'Rp ' . number_format($booking->layanan->harga ?? 0, 0, ',', '.'),
                    'date' => $booking->tanggalbooking ? $booking->tanggalbooking->format('d M Y') : '-',
                    'time' => $booking->jam,
                    'status' => $booking->status,
                    'payment_status' => $paymentStatus,
                    'staff' => $staffName ?: 'General Staff',
                    'resource' => $resourceName ?: 'General Facility',
                    'notes' => $booking->catatan ?? '-',
                    'order_id' => $booking->payment?->order_id ?? '-',
                    'snap_token' => $booking->payment?->snap_token ?? null,
                    'rescheduled_from_date' => $booking->rescheduled_from_date ? $booking->rescheduled_from_date->format('d M Y') : null,
                    'rescheduled_from_time' => $booking->rescheduled_from_time ?? null,
                    'manage_url' => $booking->booking_code ? route('booking.manage', $booking->booking_code) : null,
                ];

                $warnastatus = match($booking->status) {
                    'completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                    'paid'      => 'bg-indigo-50 text-indigo-700 ring-indigo-600/20',
                    'pending'   => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                    'cancelled' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
                    default     => 'bg-gray-50 text-gray-700 ring-gray-600/20',
                };
                $statusLabel = match($booking->status) {
                    'paid'      => 'Confirmed',
                    'pending'   => 'Pending',
                    'completed' => 'Completed',
                    'cancelled' => 'Cancelled',
                    default     => ucfirst($booking->status),
                };
            @endphp

            <div class="rounded-2xl border border-bq-border bg-bq-surface p-4 shadow-xs space-y-3">
                {{-- Top Row: Code, Date & Status --}}
                <div class="flex items-center justify-between border-b border-bq-border/60 pb-2.5">
                    <div class="flex items-center gap-2">
                        <span class="font-mono text-xs font-bold text-bq-primary bg-indigo-50 px-2 py-0.5 rounded-lg border border-indigo-100">
                            #{{ $booking->booking_code ?? $booking->id }}
                        </span>
                        <span class="text-xs text-bq-text-muted font-medium">
                            {{ $booking->tanggalbooking ? $booking->tanggalbooking->format('d M') : '-' }} &bull; {{ substr($booking->jam, 0, 5) }}
                        </span>
                    </div>
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase ring-1 ring-inset {{ $warnastatus }}">
                        {{ $statusLabel }}
                    </span>
                </div>

                {{-- Middle: Customer & Service Info --}}
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-1.5">
                            <p class="font-bold text-sm text-bq-text truncate">{{ $booking->namapelanggan }}</p>
                            @if($booking->nomorhp)
                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $booking->nomorhp) }}" target="_blank" class="text-emerald-600 hover:text-emerald-700 p-0.5 shrink-0" title="Hubungi via WhatsApp">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.303-.058.116-.087.188-.173.289l-.26.303c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86.173.086.275.072.376-.043.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.043.072.043.419-.101.824z"/>
                                    </svg>
                                </a>
                            @endif
                        </div>
                        <p class="text-xs font-medium text-bq-text-muted mt-0.5 truncate">{{ $booking->layanan->namalayanan ?? 'Standard Service' }}</p>
                        <div class="mt-1 flex items-center gap-2 text-[11px] text-bq-text-subtle">
                            <span>{{ $staffResourceDisplay }}</span>
                        </div>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-sm font-extrabold text-bq-primary">
                            Rp {{ number_format($booking->layanan->harga ?? 0, 0, ',', '.') }}
                        </p>
                        <span class="inline-block mt-0.5 text-[10px] font-bold px-1.5 py-0.2 rounded {{ $paymentStatus === 'sukses' || $booking->status === 'paid' || $booking->status === 'completed' ? 'text-emerald-700 bg-emerald-50' : 'text-amber-700 bg-amber-50' }}">
                            {{ $paymentStatus === 'sukses' || $booking->status === 'paid' || $booking->status === 'completed' ? 'Paid' : 'Unpaid' }}
                        </span>
                    </div>
                </div>

                {{-- Bottom Row: Direct Actions --}}
                <div class="flex items-center justify-between pt-2.5 border-t border-bq-border/60 gap-2">
                    <button type="button"
                        @click="viewBooking({{ json_encode($bookingData) }})"
                        class="flex-1 inline-flex items-center justify-center gap-1.5 rounded-xl border border-bq-border bg-bq-background/60 py-2 px-3 text-xs font-bold text-bq-text active:bg-slate-200 transition">
                        <svg class="h-3.5 w-3.5 text-bq-text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <span>Detail</span>
                    </button>

                    @if ($booking->status === 'pending')
                        <form method="POST" action="{{ route('owner.bookings.status', $booking->id) }}" class="flex-1">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="paid">
                            <button type="submit"
                                class="w-full inline-flex items-center justify-center gap-1 rounded-xl bg-emerald-600 py-2 px-3 text-xs font-bold text-white active:bg-emerald-700 transition shadow-2xs">
                                <span>✓ Lunas</span>
                            </button>
                        </form>
                    @elseif ($booking->status === 'paid')
                        <form method="POST" action="{{ route('owner.bookings.status', $booking->id) }}" class="flex-1">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="completed">
                            <button type="submit"
                                class="w-full inline-flex items-center justify-center gap-1 rounded-xl bg-indigo-600 py-2 px-3 text-xs font-bold text-white active:bg-indigo-700 transition shadow-2xs">
                                <span>✓ Selesai</span>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-bq-border bg-bq-surface p-8 text-center">
                <p class="text-sm font-semibold text-bq-text">No bookings found</p>
                <p class="text-xs text-bq-text-muted mt-1">When customers schedule sessions, they will appear here.</p>
            </div>
        @endforelse
    </div>
