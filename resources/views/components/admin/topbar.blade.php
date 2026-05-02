@php
    $user = auth()->user();
    $userName = $user?->namalengkap ?? 'Super Admin';
    $userEmail = $user?->email ?? 'superadmin@bookqu.test';
    $initials = strtoupper(substr($userName, 0, 2));
@endphp

<div class="flex flex-col gap-4 mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold">{{ $title ?? 'Dashboard' }}</h1>
            <p class="text-sm text-slate-500">Kelola data platform BookQu</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="text-right">
                <div class="text-sm font-medium">{{ $userName }}</div>
                <div class="text-xs text-slate-500">{{ $userEmail }}</div>
            </div>
            <div class="h-10 w-10 rounded-full bg-slate-900 text-white flex items-center justify-center text-sm font-semibold">
                {{ $initials }}
            </div>
        </div>
    </div>
</div>
