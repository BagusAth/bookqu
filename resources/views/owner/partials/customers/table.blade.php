    {{-- ── Customer Table ── --}}
    @if($customers->isNotEmpty())
    <div class="rounded-2xl border border-bq-border bg-bq-surface shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-bq-border bg-bq-background/60 font-semibold uppercase tracking-wider text-bq-text-muted">
                        <th class="px-5 py-3.5">Customer</th>
                        <th class="px-5 py-3.5">Phone (WhatsApp)</th>
                        <th class="px-5 py-3.5">Email</th>
                        <th class="px-5 py-3.5 text-center">Total Bookings</th>
                        <th class="px-5 py-3.5">Total Spending</th>
                        <th class="px-5 py-3.5">Last Booking</th>
                        <th class="px-5 py-3.5">Upcoming</th>
                        <th class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-bq-border">
                    @foreach($customers as $c)
                    <tr class="hover:bg-bq-background/40 transition">
                        {{-- Name + VIP badge --}}
                        <td class="px-5 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 font-bold text-indigo-700 text-xs shrink-0">
                                    {{ strtoupper(substr($c->name ?: 'C', 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-semibold text-bq-text text-sm">{{ $c->name ?: 'Customer' }}</p>
                                    @if($c->total_bookings >= 3)
                                        <span class="inline-flex rounded bg-amber-50 text-amber-700 px-1.5 py-0.5 text-[10px] font-bold">VIP Regular</span>
                                    @endif
                                </div>
                            </div>
                        </td>

                        {{-- Phone --}}
                        <td class="px-5 py-4 whitespace-nowrap">
                            @if($c->phone && $c->phone !== '-')
                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $c->phone) }}" target="_blank"
                                   class="inline-flex items-center gap-1 font-mono text-emerald-700 hover:underline">
                                    {{ $c->phone }}
                                    <span class="text-[10px] bg-emerald-100 text-emerald-800 px-1 rounded">WA</span>
                                </a>
                            @else
                                <span class="text-bq-text-muted">—</span>
                            @endif
                        </td>

                        {{-- Email --}}
                        <td class="px-5 py-4 whitespace-nowrap text-bq-text-muted">{{ $c->email ?: '—' }}</td>

                        {{-- Total Bookings --}}
                        <td class="px-5 py-4 whitespace-nowrap text-center">
                            <span class="rounded-full bg-slate-100 text-slate-800 font-bold px-2.5 py-0.5 text-xs">{{ $c->total_bookings }}</span>
                        </td>

                        {{-- Total Spending (from payments) --}}
                        <td class="px-5 py-4 whitespace-nowrap font-semibold text-emerald-700">
                            {{ $c->formatted_spent }}
                        </td>

                        {{-- Last Booking --}}
                        <td class="px-5 py-4 whitespace-nowrap text-bq-text-muted">{{ $c->last_booking }}</td>

                        {{-- Upcoming --}}
                        <td class="px-5 py-4 whitespace-nowrap">
                            @if($c->upcoming_booking)
                                <span class="inline-flex rounded-full bg-indigo-50 text-indigo-700 px-2 py-0.5 text-[11px] font-medium">
                                    {{ $c->upcoming_booking }}
                                </span>
                            @else
                                <span class="text-bq-text-muted text-[11px]">—</span>
                            @endif
                        </td>

                        {{-- Actions --}}
                        <td class="px-5 py-4 whitespace-nowrap text-right">
                            <button type="button"
                                    @click="openCustomer('{{ addslashes($c->identifier) }}')"
                                    id="btn-view-customer-{{ $loop->index }}"
                                    class="inline-flex items-center gap-1 rounded-lg border border-bq-border bg-bq-surface px-2.5 py-1.5 text-xs font-semibold text-bq-text hover:bg-bq-background transition">
                                <svg class="h-3.5 w-3.5 text-bq-text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                Detail CRM
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($customers->hasPages())
        <div class="border-t border-bq-border px-5 py-4">
            {{ $customers->links() }}
        </div>
        @endif
    </div>

    @else
    {{-- ── Empty State ── --}}
    <div class="rounded-2xl border border-dashed border-bq-border bg-bq-surface p-12 text-center">
        <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
        </div>
        @if($search)
            <h3 class="text-sm font-bold text-bq-text">Tidak ada customer yang cocok</h3>
            <p class="mt-1 text-xs text-bq-text-muted max-w-sm mx-auto">
                Pencarian "<strong>{{ $search }}</strong>" tidak menemukan customer. Coba ubah kata kunci pencarian.
            </p>
            <a href="{{ route('owner.customers') }}" class="mt-4 inline-flex items-center gap-1 rounded-xl border border-bq-border px-4 py-2 text-xs font-semibold text-bq-text hover:bg-bq-background transition">
                Tampilkan semua customer
            </a>
        @else
            <h3 class="text-sm font-bold text-bq-text">Belum ada data customer</h3>
            <p class="mt-1 text-xs text-bq-text-muted max-w-sm mx-auto">
                Saat customer melakukan booking pada halaman publik bisnis Anda, profil dan riwayat mereka otomatis terakumulasi di sini.
            </p>
        @endif
    </div>
    @endif
