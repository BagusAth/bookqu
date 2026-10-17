{{-- Controls Bar: View Switcher, Date Navigator, Filters --}}
<div class="rounded-2xl border border-[#e7e2f7] bg-white p-3.5 sm:p-5 shadow-[0_4px_20px_rgba(35,26,61,0.03)] space-y-3.5">
    <div class="flex flex-col xl:flex-row gap-3 xl:items-center xl:justify-between">
        {{-- Left cluster: View Switcher & Date Navigation --}}
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 sm:gap-3">
            {{-- Segmented View Switcher Tabs (Day / Week / Month) --}}
            <div class="flex items-center gap-1 rounded-xl bg-[#f7f7fa] p-1 border border-[#e7e2f7] w-full sm:w-auto justify-between sm:justify-start shrink-0">
                <a
                    href="{{ route('owner.calendar', ['view' => 'day', 'date' => $currentDate->toDateString(), 'service_id' => $selectedService, 'status' => $selectedStatus]) }}"
                    class="flex-1 sm:flex-none text-center rounded-lg px-3.5 py-1.5 text-xs font-bold transition-all duration-150 cursor-pointer {{ $view === 'day' ? 'bg-[#382186] text-white shadow-2xs' : 'text-[#6e6584] hover:text-[#231a3d] hover:bg-white/60' }}"
                >
                    Day
                </a>
                <a
                    href="{{ route('owner.calendar', ['view' => 'week', 'date' => $currentDate->toDateString(), 'service_id' => $selectedService, 'status' => $selectedStatus]) }}"
                    class="flex-1 sm:flex-none text-center rounded-lg px-3.5 py-1.5 text-xs font-bold transition-all duration-150 cursor-pointer {{ $view === 'week' ? 'bg-[#382186] text-white shadow-2xs' : 'text-[#6e6584] hover:text-[#231a3d] hover:bg-white/60' }}"
                >
                    Week
                </a>
                <a
                    href="{{ route('owner.calendar', ['view' => 'month', 'date' => $currentDate->toDateString(), 'service_id' => $selectedService, 'status' => $selectedStatus]) }}"
                    class="flex-1 sm:flex-none text-center rounded-lg px-3.5 py-1.5 text-xs font-bold transition-all duration-150 cursor-pointer {{ $view === 'month' ? 'bg-[#382186] text-white shadow-2xs' : 'text-[#6e6584] hover:text-[#231a3d] hover:bg-white/60' }}"
                >
                    Month
                </a>
            </div>

            {{-- Date Navigation --}}
            <div class="flex items-center justify-between sm:justify-start gap-2 w-full sm:w-auto">
                <a
                    href="{{ route('owner.calendar', ['view' => $view, 'date' => $todayDate, 'service_id' => $selectedService, 'status' => $selectedStatus]) }}"
                    class="craft-btn rounded-xl border border-[#e7e2f7] bg-[#f7f7fa] hover:bg-[#e7e2f7] px-3 py-1.5 text-xs font-bold text-[#382186] shadow-2xs active:scale-95 shrink-0"
                >
                    Hari Ini
                </a>
                <div class="flex-1 sm:flex-none flex items-center justify-between rounded-xl border border-[#e7e2f7] bg-white shadow-2xs overflow-hidden">
                    <a
                        href="{{ route('owner.calendar', ['view' => $view, 'date' => $prevDate, 'service_id' => $selectedService, 'status' => $selectedStatus]) }}"
                        class="p-2 text-[#6e6584] hover:text-[#382186] hover:bg-[#f7f7fa] transition border-r border-[#e7e2f7] active:scale-95"
                        aria-label="Previous"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <span class="px-2 sm:px-3.5 py-1.5 text-xs font-bold text-[#231a3d] min-w-[110px] sm:min-w-[150px] text-center tracking-wide truncate">
                        {{ $dateLabel }}
                    </span>
                    <a
                        href="{{ route('owner.calendar', ['view' => $view, 'date' => $nextDate, 'service_id' => $selectedService, 'status' => $selectedStatus]) }}"
                        class="p-2 text-[#6e6584] hover:text-[#382186] hover:bg-[#f7f7fa] transition border-l border-[#e7e2f7] active:scale-95"
                        aria-label="Next"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>

        {{-- Right cluster: Service & Status Filters --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:flex items-center gap-2 w-full xl:w-auto">
            <select
                onchange="window.location.href = updateCalendarFilter('service_id', this.value)"
                class="w-full xl:w-44 rounded-xl border border-[#e7e2f7] bg-[#f7f7fa] hover:bg-white px-3 py-1.5 text-xs font-semibold text-[#231a3d] focus:border-[#382186] focus:ring-2 focus:ring-[#b499ff]/30 focus:outline-none transition shadow-2xs truncate"
            >
                <option value="all" {{ $selectedService === 'all' ? 'selected' : '' }}>Semua Layanan</option>
                @foreach ($services as $service)
                    <option value="{{ $service->id }}" {{ (string)$selectedService === (string)$service->id ? 'selected' : '' }}>{{ $service->namalayanan }}</option>
                @endforeach
            </select>

            <select
                onchange="window.location.href = updateCalendarFilter('status', this.value)"
                class="w-full xl:w-44 rounded-xl border border-[#e7e2f7] bg-[#f7f7fa] hover:bg-white px-3 py-1.5 text-xs font-semibold text-[#231a3d] focus:border-[#382186] focus:ring-2 focus:ring-[#b499ff]/30 focus:outline-none transition shadow-2xs truncate"
            >
                <option value="all" {{ $selectedStatus === 'all' ? 'selected' : '' }}>Semua Status</option>
                <option value="available" {{ $selectedStatus === 'available' ? 'selected' : '' }}>Slot Kosong (Available)</option>
                <option value="paid" {{ $selectedStatus === 'paid' ? 'selected' : '' }}>Confirmed (Lunas)</option>
                <option value="pending" {{ $selectedStatus === 'pending' ? 'selected' : '' }}>Pending (Menunggu Bayar)</option>
                <option value="completed" {{ $selectedStatus === 'completed' ? 'selected' : '' }}>Completed (Selesai)</option>
                <option value="cancelled" {{ $selectedStatus === 'cancelled' ? 'selected' : '' }}>Cancelled (Dibatalkan)</option>
                <option value="blocked" {{ $selectedStatus === 'blocked' ? 'selected' : '' }}>Blocked (Libur / Tutup)</option>
            </select>
        </div>
    </div>

    {{-- Legend Indicators (Horizontal scroll on mobile) --}}
    <div class="flex items-center gap-3 overflow-x-auto pb-1 sm:pb-0 pt-3.5 border-t border-[#e7e2f7] text-xs text-[#6e6584] no-scrollbar -mx-4 px-4 sm:mx-0 sm:px-0 sm:flex-wrap">
        <span class="font-extrabold text-[#231a3d] shrink-0">Indikator:</span>
        <div class="flex items-center gap-1.5 shrink-0">
            <span class="h-2.5 w-2.5 rounded-full bg-emerald-500 shadow-2xs"></span>
            <span class="font-medium">Confirmed</span>
        </div>
        <div class="flex items-center gap-1.5 shrink-0">
            <span class="h-2.5 w-2.5 rounded-full bg-[#ffb84d] shadow-2xs"></span>
            <span class="font-medium">Pending</span>
        </div>
        <div class="flex items-center gap-1.5 shrink-0">
            <span class="h-2.5 w-2.5 rounded-full bg-sky-500 shadow-2xs"></span>
            <span class="font-medium">Completed</span>
        </div>
        <div class="flex items-center gap-1.5 shrink-0">
            <span class="h-2.5 w-2.5 rounded-full bg-[#b499ff] shadow-2xs"></span>
            <span class="font-medium">Slot Tersedia</span>
        </div>
        <div class="flex items-center gap-1.5 shrink-0">
            <span class="h-2.5 w-2.5 rounded-full bg-rose-400 shadow-2xs"></span>
            <span class="font-medium">Cancelled</span>
        </div>
        <div class="flex items-center gap-1.5 shrink-0">
            <span class="h-2.5 w-2.5 rounded-full bg-[#6e6584] shadow-2xs"></span>
            <span class="font-medium">Libur</span>
        </div>
    </div>
</div>
