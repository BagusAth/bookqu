{{-- Header Section --}}
<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-extrabold text-[#231a3d] tracking-tight sm:text-3xl">Calendar &amp; Jadwal Operasional</h1>
    </div>
    <div class="flex items-center gap-2.5 w-full sm:w-auto">
        <a
            href="{{ route('owner.schedule') }}"
            class="craft-btn flex-1 sm:flex-none justify-center inline-flex items-center gap-2 rounded-xl border border-[#e7e2f7] bg-white px-3.5 sm:px-4 py-2.5 text-xs sm:text-sm font-bold text-[#231a3d] hover:bg-[#f7f7fa] hover:border-[#b499ff] shadow-2xs active:scale-95"
        >
            <svg class="h-4 w-4 text-[#382186]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            <span class="truncate">Kelola Schedule</span>
        </a>
        <a
            href="{{ route('owner.bookings') }}"
            class="craft-btn flex-1 sm:flex-none justify-center inline-flex items-center gap-2 rounded-xl bg-[#382186] hover:bg-[#2d1a6d] px-3.5 sm:px-4 py-2.5 text-xs sm:text-sm font-bold text-white shadow-xs active:scale-95"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            <span class="truncate">Daftar Bookings</span>
        </a>
    </div>
</div>
