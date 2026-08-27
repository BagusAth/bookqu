{{-- View Booking Details Modal Component --}}
<div
    x-data="{
        buka: false,
        booking: null,
        formatDate(dateString) {
            if (!dateString) return '';
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            return new Date(dateString).toLocaleDateString('en-US', options);
        }
    }"
    @open-view-booking.window="
        booking = $event.detail.booking;
        buka = true;
    "
    x-cloak
>
    {{-- Overlay --}}
    <div
        x-show="buka"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-[60] bg-black/40 backdrop-blur-sm"
        @click="buka = false"
    ></div>

    {{-- Modal --}}
    <div
        x-show="buka"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        class="fixed inset-0 z-[70] flex items-center justify-center p-4"
    >
        <div class="w-full max-w-md rounded-2xl border border-bq-border bg-bq-surface shadow-2xl" @click.stop>
            {{-- Header --}}
            <div class="flex items-center justify-between border-b border-bq-border px-6 py-4">
                <div>
                    <h2 class="text-lg font-semibold text-bq-text">Booking Details</h2>
                    <p class="text-sm text-bq-text-muted">Information about the customer and schedule.</p>
                </div>
                <button @click="buka = false" class="rounded-lg p-1.5 text-bq-text-subtle transition-colors hover:bg-bq-background hover:text-bq-text">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="p-6 space-y-4" x-show="booking">
                
                <div class="flex items-center gap-3 mb-6">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-bq-primary/10 text-bq-primary">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-base font-bold text-bq-text" x-text="booking?.namapelanggan"></p>
                        <p class="text-sm text-bq-text-muted" x-text="booking?.booking_code"></p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="rounded-lg border border-bq-border bg-bq-background/50 p-3">
                        <p class="text-xs font-medium text-bq-text-muted">Phone Number</p>
                        <p class="mt-1 text-sm font-semibold text-bq-text" x-text="booking?.nomorhp"></p>
                    </div>
                    <div class="rounded-lg border border-bq-border bg-bq-background/50 p-3 overflow-hidden">
                        <p class="text-xs font-medium text-bq-text-muted">Email</p>
                        <p class="mt-1 text-sm font-semibold text-bq-text truncate" x-text="booking?.email" :title="booking?.email"></p>
                    </div>
                </div>

                <div class="rounded-lg border border-bq-border bg-bq-background/50 p-3">
                    <p class="text-xs font-medium text-bq-text-muted">Schedule</p>
                    <p class="mt-1 text-sm font-semibold text-bq-text">
                        <span x-text="formatDate(booking?.tanggalbooking)"></span> at <span x-text="booking?.jam"></span>
                    </p>
                </div>

                <div class="rounded-lg border border-bq-border bg-bq-background/50 p-3" x-show="booking?.catatan">
                    <p class="text-xs font-medium text-bq-text-muted">Notes</p>
                    <p class="mt-1 text-sm text-bq-text" x-text="booking?.catatan"></p>
                </div>

                <div class="flex items-center justify-between rounded-lg border border-bq-border bg-bq-primary/5 p-3">
                    <p class="text-xs font-medium text-bq-primary">Status</p>
                    <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium"
                        :class="{
                            'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20': booking?.status === 'completed' || booking?.status === 'paid',
                            'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20': booking?.status === 'pending',
                            'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/20': booking?.status === 'cancelled' || booking?.status === 'refunded'
                        }"
                        x-text="(booking?.status || '').toUpperCase()">
                    </span>
                </div>
            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-end border-t border-bq-border px-6 py-4">
                <button type="button" @click="buka = false" class="rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm font-medium text-bq-text transition-all hover:bg-bq-background">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
