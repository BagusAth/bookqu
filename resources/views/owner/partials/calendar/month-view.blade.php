    {{-- Month View --}}
    @if ($view === 'month')
        @php
            $startOfMonth = $currentDate->copy()->startOfMonth();
            $daysInMonth = $currentDate->daysInMonth;
            $dayOfWeekIso = $startOfMonth->dayOfWeekIso; // 1 = Monday .. 7 = Sunday
            $startDayOffset = $dayOfWeekIso - 1; // 0 for Monday
            $totalCells = (int) ceil(($startDayOffset + $daysInMonth) / 7) * 7;
        @endphp

        <div class="rounded-2xl border border-[#e7e2f7] bg-white shadow-[0_4px_24px_rgba(35,26,61,0.03)] p-3.5 sm:p-5 space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 pb-3 border-b border-[#e7e2f7]">
                <h2 class="text-base font-extrabold text-[#231a3d]">{{ $currentDate->translatedFormat('F Y') }}</h2>
                <span class="text-xs text-[#6e6584] font-medium">Klik tanggal untuk melihat jadwal harian lengkap</span>
            </div>

            <div class="grid grid-cols-7 gap-px rounded-2xl border border-[#e7e2f7] bg-[#e7e2f7] overflow-hidden text-center text-xs">
                @foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'] as $dayName)
                    <div class="bg-[#f7f7fa] py-2 sm:py-2.5 font-extrabold text-[#6e6584] uppercase tracking-wider text-[10px] sm:text-[11px]">
                        <span class="sm:hidden">{{ substr($dayName, 0, 3) }}</span>
                        <span class="hidden sm:inline">{{ $dayName }}</span>
                    </div>
                @endforeach

                @foreach (range(0, $totalCells - 1) as $cell)
                    @php
                        $dayNum = $cell - $startDayOffset + 1;
                        $isValidDay = ($dayNum >= 1 && $dayNum <= $daysInMonth);
                        $cellDate = $isValidDay ? $startOfMonth->copy()->addDays($dayNum - 1)->toDateString() : null;
                        $dayBookings = $isValidDay ? ($monthBookings->get($dayNum) ?? collect()) : collect();
                        $daySchedules = $isValidDay ? ($monthSchedules->get($dayNum) ?? collect()) : collect();
                        $isBlocked = $cellDate && isset($blockedDates[$cellDate]);
                        $isToday = $cellDate && \Carbon\Carbon::parse($cellDate)->isToday();

                        $confirmedCount = $dayBookings->where('status', 'paid')->count();
                        $pendingCount = $dayBookings->where('status', 'pending')->count();
                        $availCount = $daySchedules->filter(fn($s) => $s->getAvailabilityStatus() === \App\Models\Schedule::STATUS_AVAILABLE)->count();
                    @endphp

                    <div class="bg-white min-h-[58px] sm:min-h-[96px] p-1 sm:p-2 text-left relative transition duration-150 hover:bg-[#f7f7fa] {{ !$isValidDay ? 'bg-[#f7f7fa]/60' : '' }}">
                        @if ($isValidDay)
                            <a
                                href="{{ route('owner.calendar', ['view' => 'day', 'date' => $cellDate, 'service_id' => $selectedService, 'status' => $selectedStatus]) }}"
                                class="block h-full cursor-pointer group"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] sm:text-xs font-extrabold {{ $isToday ? 'inline-flex h-5 w-5 items-center justify-center rounded-full bg-[#382186] text-white shadow-2xs' : 'text-[#231a3d] group-hover:text-[#382186]' }}">
                                        {{ $dayNum }}
                                    </span>
                                    @if ($isBlocked)
                                        <span class="rounded bg-[#e7e2f7] px-1 text-[8px] font-bold text-[#6e6584]" title="Alasan: {{ $blockedDates[$cellDate] ?: 'Libur Operasional' }}">
                                            <span class="sm:hidden">🔒</span>
                                            <span class="hidden sm:inline">Libur</span>
                                        </span>
                                    @endif
                                </div>

                                {{-- Desktop: Full Text Badges --}}
                                <div class="hidden sm:block mt-1.5 space-y-1">
                                    @if ($confirmedCount > 0)
                                        <span class="block truncate rounded-md bg-emerald-50 px-1.5 py-0.5 text-[10px] font-bold text-emerald-800 border border-emerald-200/50">
                                            {{ $confirmedCount }} Confirmed
                                        </span>
                                    @endif
                                    @if ($pendingCount > 0)
                                        <span class="block truncate rounded-md bg-[#fff8eb] px-1.5 py-0.5 text-[10px] font-bold text-[#875000] border border-[#ffb84d]/50">
                                            {{ $pendingCount }} Pending
                                        </span>
                                    @endif
                                    @if ($availCount > 0)
                                        <span class="block truncate rounded-md bg-[#f3effe] px-1.5 py-0.5 text-[10px] font-bold text-[#382186] border border-[#b499ff]/40">
                                            {{ $availCount }} Slot Tersedia
                                        </span>
                                    @endif
                                </div>

                                {{-- Mobile: Compact Event Indicators --}}
                                <div class="flex sm:hidden flex-wrap items-center justify-center gap-1 mt-1">
                                    @if ($confirmedCount > 0)
                                        <span class="inline-flex items-center justify-center h-4 min-w-4 px-1 rounded-full bg-emerald-500 text-white text-[8px] font-black shadow-2xs" title="{{ $confirmedCount }} Confirmed">
                                            {{ $confirmedCount }}
                                        </span>
                                    @endif
                                    @if ($pendingCount > 0)
                                        <span class="inline-flex items-center justify-center h-4 min-w-4 px-1 rounded-full bg-[#ffb84d] text-white text-[8px] font-black shadow-2xs" title="{{ $pendingCount }} Pending">
                                            {{ $pendingCount }}
                                        </span>
                                    @endif
                                    @if ($availCount > 0)
                                        <span class="inline-flex items-center justify-center h-4 min-w-4 px-1 rounded-full bg-[#b499ff] text-white text-[8px] font-black shadow-2xs" title="{{ $availCount }} Slot Tersedia">
                                            {{ $availCount }}
                                        </span>
                                    @endif
                                </div>
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif
