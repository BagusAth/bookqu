    {{-- ── Bookings Desktop Table (Screen >= sm) ── --}}
    <div class="hidden sm:block rounded-2xl border border-bq-border bg-bq-surface shadow-xs overflow-hidden" id="bookings-table-card">
        <div class="overflow-x-auto">
            <table class="w-full text-left" id="bookings-table">
                <thead>
                    <tr class="border-b border-bq-border bg-bq-background/60 text-xs font-bold uppercase tracking-wider text-bq-text-muted">
                        <th class="px-3.5 py-3 lg:px-4 lg:py-3.5">Booking ID</th>
                        <th class="px-3.5 py-3 lg:px-4 lg:py-3.5">Customer</th>
                        <th class="px-3.5 py-3 lg:px-4 lg:py-3.5">Service</th>
                        <th class="px-3.5 py-3 lg:px-4 lg:py-3.5">Date &amp; Time</th>
                        <th class="px-3.5 py-3 lg:px-4 lg:py-3.5">Staff / Resource</th>
                        <th class="px-3.5 py-3 lg:px-4 lg:py-3.5">Amount</th>
                        <th class="px-3.5 py-3 lg:px-4 lg:py-3.5 text-center">Payment</th>
                        <th class="px-3.5 py-3 lg:px-4 lg:py-3.5 text-center">Status</th>
                        <th class="px-3.5 py-3 lg:px-4 lg:py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-bq-border text-xs">
                    @forelse ($daftarbooking as $booking)
                        @php
                            $paymentStatus = $booking->payment?->status ?? ($booking->status === 'paid' ? 'sukses' : 'pending');
                            $staffName = $booking->layanan?->staff?->pluck('name')->join(', ');
                            $resourceName = $booking->layanan?->resources?->pluck('name')->join(', ');
                            $staffResourceDisplay = $staffName ?: ($resourceName ?: 'General Staff / Spot');
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
                        @endphp
                        <tr class="transition-colors hover:bg-bq-background/40">
                            {{-- Booking ID --}}
                            <td class="whitespace-nowrap px-3.5 py-3 lg:px-4 lg:py-3.5">
                                <span class="font-mono text-xs font-bold text-bq-primary bg-indigo-50 px-2 py-0.5 rounded-lg border border-indigo-100">
                                    #{{ $booking->booking_code ?? $booking->id }}
                                </span>
                            </td>

                            {{-- Customer --}}
                            <td class="px-3.5 py-3 lg:px-4 lg:py-3.5 min-w-[130px] max-w-[180px]">
                                <div>
                                    <p class="text-xs sm:text-sm font-bold text-bq-text truncate" title="{{ $booking->namapelanggan }}">{{ $booking->namapelanggan }}</p>
                                    <div class="flex items-center gap-1.5 text-[11px] text-bq-text-muted mt-0.5 truncate">
                                        @if($booking->nomorhp)
                                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $booking->nomorhp) }}" target="_blank" class="hover:text-emerald-600 font-mono text-[11px]">
                                                {{ $booking->nomorhp }}
                                            </a>
                                        @elseif($booking->email)
                                            <span class="text-[11px] truncate">{{ $booking->email }}</span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{-- Service --}}
                            <td class="px-3.5 py-3 lg:px-4 lg:py-3.5">
                                <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700 max-w-[140px] truncate" title="{{ $booking->layanan->namalayanan ?? 'Standard Service' }}">
                                    {{ $booking->layanan->namalayanan ?? 'Standard Service' }}
                                </span>
                            </td>

                            {{-- Date & Time --}}
                            <td class="whitespace-nowrap px-3.5 py-3 lg:px-4 lg:py-3.5">
                                <p class="text-xs font-bold text-bq-text">{{ $booking->tanggalbooking ? $booking->tanggalbooking->format('d M Y') : '-' }}</p>
                                <p class="text-[11px] text-bq-text-muted font-mono mt-0.5">{{ substr($booking->jam, 0, 5) }} WIB</p>
                            </td>

                            {{-- Staff / Resource --}}
                            <td class="whitespace-nowrap px-3.5 py-3 lg:px-4 lg:py-3.5 text-xs text-bq-text-muted">
                                <div class="flex items-center gap-1.5" title="{{ $staffResourceDisplay }}">
                                    <span class="h-2 w-2 rounded-full shrink-0 {{ $staffName ? 'bg-indigo-500' : ($resourceName ? 'bg-sky-500' : 'bg-slate-400') }}"></span>
                                    <span class="max-w-[110px] truncate">{{ $staffResourceDisplay }}</span>
                                </div>
                            </td>

                            {{-- Amount --}}
                            <td class="whitespace-nowrap px-3.5 py-3 lg:px-4 lg:py-3.5 text-xs font-extrabold text-bq-text">
                                Rp {{ number_format($booking->layanan->harga ?? 0, 0, ',', '.') }}
                            </td>

                            {{-- Payment Status --}}
                            <td class="whitespace-nowrap px-3.5 py-3 lg:px-4 lg:py-3.5 text-center">
                                @if ($paymentStatus === 'sukses' || $booking->status === 'paid' || $booking->status === 'completed')
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                                        Paid
                                    </span>
                                @elseif ($paymentStatus === 'expired')
                                    <span class="inline-flex items-center rounded-full bg-gray-50 px-2 py-0.5 text-[10px] font-bold text-gray-700 ring-1 ring-inset ring-gray-600/20">
                                        Expired
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700 ring-1 ring-inset ring-amber-600/20">
                                        Unpaid
                                    </span>
                                @endif
                            </td>

                            {{-- Booking Status --}}
                            <td class="whitespace-nowrap px-3.5 py-3 lg:px-4 lg:py-3.5 text-center">
                                @php
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
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase ring-1 ring-inset {{ $warnastatus }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>

                            {{-- Actions --}}
                            <td class="whitespace-nowrap px-3.5 py-3 lg:px-4 lg:py-3.5 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    {{-- View Detail button --}}
                                    <button type="button"
                                        @click="viewBooking({{ json_encode($bookingData) }})"
                                        class="inline-flex items-center gap-1 rounded-lg border border-bq-border bg-bq-surface px-2.5 py-1.5 text-xs font-medium text-bq-text hover:bg-bq-background transition">
                                        <svg class="h-3.5 w-3.5 text-bq-text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        Detail
                                    </button>

                                    {{-- FS-010: Dropdown aksi ubah status booking --}}
                                    @if (in_array($booking->status, ['paid', 'pending']))
                                        <div class="relative inline-block text-left" x-data="{ open: false }">
                                            <button @click="open = !open"
                                                class="inline-flex items-center gap-1 rounded-lg border border-bq-border bg-bq-surface px-2.5 py-1.5 text-xs font-medium text-bq-text-muted transition hover:border-bq-border-strong hover:text-bq-text"
                                                id="action-btn-{{ $booking->id }}">
                                                Status
                                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                                </svg>
                                            </button>
                                            <div x-show="open" @click.outside="open = false"
                                                class="absolute right-0 z-20 mt-1 w-44 origin-top-right rounded-xl border border-bq-border bg-white shadow-xl overflow-hidden"
                                                style="display: none;">
                                                @if ($booking->status === 'pending')
                                                    <form method="POST" action="{{ route('owner.bookings.status', $booking->id) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="status" value="paid">
                                                        <button type="submit"
                                                            class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-xs font-medium text-emerald-700 hover:bg-emerald-50"
                                                            id="mark-paid-{{ $booking->id }}">
                                                            ✓ Konfirmasi Lunas
                                                        </button>
                                                    </form>
                                                @endif
                                                @if ($booking->status === 'paid')
                                                    <form method="POST" action="{{ route('owner.bookings.status', $booking->id) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="status" value="completed">
                                                        <button type="submit"
                                                            class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-xs font-medium text-emerald-700 hover:bg-emerald-50"
                                                            id="mark-completed-{{ $booking->id }}">
                                                            ✓ Tandai Selesai
                                                        </button>
                                                    </form>
                                                @endif
                                                <button type="button"
                                                    @click="open = false; $dispatch('open-owner-reschedule', { booking: {{ json_encode($bookingData) }} })"
                                                    class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-xs font-medium text-indigo-700 hover:bg-indigo-50 border-t border-slate-100 cursor-pointer">
                                                    <svg class="h-3.5 w-3.5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                    </svg>
                                                    📅 Ubah Jadwal
                                                </button>
                                                <form id="form-cancel-booking-{{ $booking->id }}" method="POST" action="{{ route('owner.bookings.status', $booking->id) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="cancelled">
                                                    <button type="button"
                                                        class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-xs font-medium text-rose-700 hover:bg-rose-50 border-t border-slate-100"
                                                        id="cancel-booking-{{ $booking->id }}"
                                                        @click="$dispatch('open-confirm', { title: 'Batalkan Booking?', message: 'Apakah Anda yakin ingin membatalkan booking ini?', formId: 'form-cancel-booking-{{ $booking->id }}' })">
                                                        ✕ Batalkan Booking
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-12 text-center text-sm text-bq-text-muted">
                                <div class="mx-auto max-w-sm text-center">
                                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-slate-500">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <p class="font-semibold text-bq-text">No bookings found</p>
                                    <p class="text-xs text-bq-text-muted mt-1">When customers schedule sessions, they will appear here in real-time.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

