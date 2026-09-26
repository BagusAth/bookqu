    {{-- ── Slide-Over Detail Drawer ── --}}
    <div x-show="detailOpen"
         class="fixed inset-0 z-50 overflow-hidden"
         style="display: none;"
         x-transition:enter="transition-opacity ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="detailOpen = false"></div>

        <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
            <div x-show="detailOpen"
                 x-transition:enter="transform transition ease-in-out duration-300"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transform transition ease-in-out duration-200"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="translate-x-full"
                 class="w-screen max-w-lg bg-white shadow-2xl flex flex-col">

                {{-- Drawer Header --}}
                <div class="p-6 border-b border-bq-border bg-slate-50/70 flex items-center justify-between shrink-0">
                    <template x-if="loading">
                        <div class="flex items-center gap-3">
                            <div class="h-11 w-11 rounded-2xl bg-slate-200 animate-pulse"></div>
                            <div class="space-y-2">
                                <div class="h-4 w-32 rounded bg-slate-200 animate-pulse"></div>
                                <div class="h-3 w-24 rounded bg-slate-200 animate-pulse"></div>
                            </div>
                        </div>
                    </template>
                    <template x-if="!loading && activeCustomer">
                        <div class="flex items-center gap-3">
                            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-600 text-white font-bold text-base">
                                <span x-text="activeCustomer.name ? activeCustomer.name.charAt(0).toUpperCase() : 'C'"></span>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-bq-text" x-text="activeCustomer.name"></h3>
                                <p class="text-xs text-bq-text-muted" x-text="activeCustomer.email"></p>
                            </div>
                        </div>
                    </template>
                    <button type="button" @click="detailOpen = false"
                            class="rounded-lg p-1.5 text-bq-text-muted hover:bg-slate-200 hover:text-bq-text transition shrink-0">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Tabs --}}
                <div class="border-b border-bq-border px-6 flex items-center gap-4 text-xs font-semibold shrink-0">
                    <button type="button" @click="activeTab = 'overview'" id="tab-overview"
                            class="py-3 border-b-2 transition"
                            :class="activeTab === 'overview' ? 'border-bq-primary text-bq-primary' : 'border-transparent text-bq-text-muted'">
                        Overview
                    </button>
                    <button type="button" @click="activeTab = 'history'" id="tab-history"
                            class="py-3 border-b-2 transition"
                            :class="activeTab === 'history' ? 'border-bq-primary text-bq-primary' : 'border-transparent text-bq-text-muted'">
                        Booking History
                    </button>
                    <button type="button" @click="activeTab = 'payments'" id="tab-payments"
                            class="py-3 border-b-2 transition"
                            :class="activeTab === 'payments' ? 'border-bq-primary text-bq-primary' : 'border-transparent text-bq-text-muted'">
                        Payments
                    </button>
                    <button type="button" @click="activeTab = 'notes'" id="tab-notes"
                            class="py-3 border-b-2 transition"
                            :class="activeTab === 'notes' ? 'border-bq-primary text-bq-primary' : 'border-transparent text-bq-text-muted'">
                        Notes
                    </button>
                </div>

                {{-- Drawer Body --}}
                <div class="flex-1 overflow-y-auto p-6 space-y-4">

                    {{-- Loading skeleton --}}
                    <template x-if="loading">
                        <div class="space-y-3">
                            <div class="h-20 rounded-xl bg-slate-100 animate-pulse"></div>
                            <div class="h-32 rounded-xl bg-slate-100 animate-pulse"></div>
                        </div>
                    </template>

                    <template x-if="!loading && activeCustomer">
                        <div>

                            {{-- TAB 1: OVERVIEW --}}
                            <div x-show="activeTab === 'overview'" class="space-y-4">
                                {{-- Stats grid --}}
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="rounded-xl border border-bq-border p-3.5 bg-slate-50/50">
                                        <p class="text-[11px] text-bq-text-muted">Total Spent (LTV)</p>
                                        <p class="text-base font-bold text-emerald-700 mt-0.5" x-text="activeCustomer.formatted_spent"></p>
                                    </div>
                                    <div class="rounded-xl border border-bq-border p-3.5 bg-slate-50/50">
                                        <p class="text-[11px] text-bq-text-muted">Rata-rata Transaksi</p>
                                        <p class="text-base font-bold text-bq-primary mt-0.5" x-text="activeCustomer.avg_transaction"></p>
                                    </div>
                                    <div class="rounded-xl border border-bq-border p-3.5 bg-slate-50/50">
                                        <p class="text-[11px] text-bq-text-muted">Total Appointments</p>
                                        <p class="text-base font-bold text-bq-text mt-0.5" x-text="activeCustomer.total_bookings + ' bookings'"></p>
                                    </div>
                                    <div class="rounded-xl border border-bq-border p-3.5 bg-slate-50/50">
                                        <p class="text-[11px] text-bq-text-muted">Bergabung Sejak</p>
                                        <p class="text-sm font-semibold text-bq-text mt-0.5" x-text="activeCustomer.first_seen"></p>
                                    </div>
                                </div>

                                {{-- Contact --}}
                                <div class="rounded-xl border border-bq-border p-4 space-y-3">
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-bq-text-muted">Contact Info</h4>
                                    <div class="space-y-2 text-xs">
                                        <div class="flex justify-between items-center">
                                            <span class="text-bq-text-muted">Phone / WhatsApp:</span>
                                            <a :href="'https://wa.me/' + (activeCustomer.phone ? activeCustomer.phone.replace(/[^0-9]/g, '') : '')"
                                               target="_blank"
                                               class="font-mono text-emerald-700 font-bold hover:underline"
                                               x-text="activeCustomer.phone"></a>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-bq-text-muted">Email:</span>
                                            <span class="font-medium text-bq-text" x-text="activeCustomer.email"></span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-bq-text-muted">Last Visited:</span>
                                            <span class="text-bq-text" x-text="activeCustomer.last_booking"></span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-bq-text-muted">Next Scheduled:</span>
                                            <template x-if="activeCustomer.upcoming_booking">
                                                <span class="font-semibold text-indigo-600" x-text="activeCustomer.upcoming_booking"></span>
                                            </template>
                                            <template x-if="!activeCustomer.upcoming_booking">
                                                <span class="text-bq-text-muted">—</span>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                {{-- Services Used --}}
                                <div class="rounded-xl border border-bq-border p-4 space-y-2">
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-bq-text-muted">Services Used</h4>
                                    <div class="flex flex-wrap gap-1.5">
                                        <template x-for="svc in (activeCustomer.services_used || [])" :key="svc">
                                            <span class="rounded-lg bg-indigo-50 text-indigo-700 px-2.5 py-1 text-xs font-medium" x-text="svc"></span>
                                        </template>
                                        <template x-if="!activeCustomer.services_used || activeCustomer.services_used.length === 0">
                                            <span class="text-bq-text-subtle text-xs">Belum ada layanan</span>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            {{-- TAB 2: BOOKING HISTORY --}}
                            <div x-show="activeTab === 'history'" style="display:none;" class="space-y-3">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-bq-text-muted">Riwayat Booking</h4>
                                <template x-if="activeCustomer.bookings && activeCustomer.bookings.length > 0">
                                    <div class="divide-y divide-bq-border rounded-xl border border-bq-border overflow-hidden">
                                        <template x-for="b in activeCustomer.bookings" :key="b.id">
                                            <div class="p-3.5 hover:bg-slate-50 transition text-xs space-y-1">
                                                <div class="flex items-center justify-between">
                                                    <div>
                                                        <span class="font-semibold text-bq-text" x-text="b.service"></span>
                                                        <p class="text-[10px] font-mono text-bq-text-muted" x-text="b.code"></p>
                                                    </div>
                                                    <span class="font-mono font-bold text-bq-primary" x-text="b.price"></span>
                                                </div>
                                                <div class="flex items-center justify-between text-bq-text-muted text-[11px]">
                                                    <span x-text="b.date + ' • ' + b.time"></span>
                                                    <span class="uppercase font-semibold px-2 py-0.5 rounded text-[10px]"
                                                          :class="{
                                                              'bg-emerald-50 text-emerald-700': b.status === 'completed',
                                                              'bg-indigo-50 text-indigo-700': b.status === 'paid',
                                                              'bg-amber-50 text-amber-700': b.status === 'pending',
                                                              'bg-rose-50 text-rose-700': b.status === 'cancelled'
                                                          }"
                                                          x-text="b.status === 'paid' ? 'Confirmed' : b.status.charAt(0).toUpperCase() + b.status.slice(1)">
                                                    </span>
                                                </div>
                                                <template x-if="b.notes">
                                                    <p class="text-[11px] text-bq-text-muted italic" x-text="'Catatan: ' + b.notes"></p>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="!activeCustomer.bookings || activeCustomer.bookings.length === 0">
                                    <p class="text-xs text-bq-text-muted text-center py-8">Tidak ada riwayat booking.</p>
                                </template>
                            </div>

                            {{-- TAB 3: PAYMENTS --}}
                            <div x-show="activeTab === 'payments'" style="display:none;" class="space-y-3">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-bq-text-muted">Riwayat Pembayaran</h4>
                                <template x-if="activeCustomer.payments && activeCustomer.payments.length > 0">
                                    <div class="divide-y divide-bq-border rounded-xl border border-bq-border overflow-hidden">
                                        <template x-for="p in activeCustomer.payments" :key="p.order_id">
                                            <div class="p-3.5 hover:bg-slate-50 transition text-xs space-y-1">
                                                <div class="flex items-center justify-between">
                                                    <div>
                                                        <span class="font-semibold text-bq-text" x-text="p.service"></span>
                                                        <p class="text-[10px] font-mono text-bq-text-muted" x-text="p.order_id"></p>
                                                    </div>
                                                    <span class="font-mono font-bold text-emerald-700" x-text="p.jumlah"></span>
                                                </div>
                                                <div class="flex items-center justify-between text-bq-text-muted text-[11px]">
                                                    <span x-text="p.date"></span>
                                                    <span class="uppercase font-semibold px-2 py-0.5 rounded text-[10px]"
                                                          :class="{
                                                              'bg-emerald-50 text-emerald-700': p.status === 'sukses',
                                                              'bg-amber-50 text-amber-700': p.status === 'pending',
                                                              'bg-rose-50 text-rose-700': p.status === 'gagal'
                                                          }"
                                                          x-text="p.status === 'sukses' ? 'Sukses' : p.status === 'pending' ? 'Pending' : 'Gagal'">
                                                    </span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="!activeCustomer.payments || activeCustomer.payments.length === 0">
                                    <p class="text-xs text-bq-text-muted text-center py-8">Belum ada riwayat pembayaran.</p>
                                </template>
                            </div>

                            {{-- TAB 4: NOTES --}}
                            <div x-show="activeTab === 'notes'" style="display:none;" class="space-y-3">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-bq-text-muted">Internal Preferences & Remarks</h4>
                                <p class="text-[11px] text-bq-text-muted">Catatan ini hanya terlihat oleh Anda (owner) — tidak ditampilkan ke customer.</p>
                                <form id="form-customer-note" class="space-y-3" @submit.prevent="saveNote()">
                                    @csrf
                                    <input type="hidden" name="customer_identifier" :value="activeCustomer.identifier">
                                    <textarea name="notes"
                                              x-model="activeCustomer.notes"
                                              rows="5"
                                              placeholder="Catatan internal tentang preferensi customer ini (misal: alergi, request khusus, preferensi instruktur)..."
                                              class="w-full rounded-xl border border-bq-border p-3 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20 resize-none"></textarea>
                                    <button type="submit"
                                            class="rounded-xl bg-bq-primary px-3.5 py-2 text-xs font-semibold text-white hover:bg-bq-primary-hover transition">
                                        Simpan Catatan
                                    </button>
                                </form>
                            </div>

                        </div>
                    </template>
                </div>

                {{-- Drawer Footer --}}
                <div class="p-6 border-t border-bq-border bg-slate-50 flex items-center justify-between shrink-0">
                    <button type="button" @click="detailOpen = false"
                            class="px-4 py-2 rounded-xl border border-bq-border bg-white text-xs font-semibold text-bq-text hover:bg-slate-100 transition">
                        Tutup
                    </button>
                    <template x-if="activeCustomer && activeCustomer.phone && activeCustomer.phone !== '-'">
                        <a :href="'https://wa.me/' + activeCustomer.phone.replace(/[^0-9]/g, '')"
                           target="_blank"
                           class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700 transition shadow-sm">
                            Hubungi via WhatsApp &rarr;
                        </a>
                    </template>
                </div>

            </div>
        </div>
    </div>
