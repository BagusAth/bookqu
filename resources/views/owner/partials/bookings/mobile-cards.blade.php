    {{-- ── Bookings Mobile Cards (Screen < sm) ── --}}
    <div class="block sm:hidden space-y-3" id="bookings-mobile-cards">
        @forelse ($daftarbooking as $booking)
            @php
                $paymentStatus = $booking->payment?->status ?? ($booking->status === 'paid' ? 'sukses' : 'pending');
                $staffName = $booking->layanan?->staff?->pluck('name')->join(', ');
                $resourceName = $booking->layanan?->resources?->pluck('name')->join(', ');
                $staffResourceDisplay = $staffName ?: ($resourceName ?: 'General Staff');
                $bookingData = [
                    'booking_code' => $booking->booking_code,
                    'namapelanggan' => $booking->namapelanggan,
                    'jam' => $booking->jam,
                    'idschedule' => $booking->idschedule,
                    'tanggalbooking' => $booking->tanggalbooking?->toDateString(),
                    'layanan' => $booking->layanan ? ['namalayanan' => $booking->layanan->namalayanan] : null,
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
                    'manage_url' => null,
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
                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $booking->nomorhp) }}" target="_blank" rel="noopener noreferrer" class="-m-2 inline-flex shrink-0 items-center justify-center rounded-lg p-2 text-emerald-600 hover:bg-emerald-50 hover:text-emerald-700" title="Hubungi via WhatsApp" aria-label="Hubungi via WhatsApp">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
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
                        @click="viewBooking(@js($bookingData))"
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
