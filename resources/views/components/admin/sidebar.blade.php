@props([
    'menuItems' => null,
    'activeMenu' => null,
])

@php
    $menuItems = $menuItems ?? [
        ['name' => 'Dashboard', 'icon' => 'dashboard', 'url' => route('admin.dashboard')],
        ['name' => 'Programs', 'icon' => 'calendar-days', 'url' => '#'],
        ['name' => 'Schedule', 'icon' => 'calendar', 'url' => '#'],
        ['name' => 'Bookings', 'icon' => 'book-check', 'url' => '#'],
        ['name' => 'Analytics', 'icon' => 'bar-chart', 'url' => '#'],
        ['name' => 'Subscription', 'icon' => 'credit-card', 'url' => '#'],
    ];

    $activeMenu = $activeMenu ?? 'Dashboard';
@endphp

<aside class="w-64 bg-slate-50 border-r border-slate-200 hidden lg:flex lg:flex-col pt-6 pb-6">
    <div class="flex items-center gap-3 px-6 mb-8">
        <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center text-white font-bold">
            B
        </div>
        <div>
            <h1 class="font-bold text-slate-900 leading-tight">BookQu Admin</h1>
            <p class="text-xs text-slate-500">Owner Portal</p>
        </div>
    </div>

    <nav class="flex-1 px-4 space-y-1">
        @foreach ($menuItems as $item)
            @php
                $isActive = $activeMenu === $item['name'];
            @endphp
            <a
                href="{{ $item['url'] }}"
                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ $isActive ? 'bg-white text-indigo-600 shadow-sm border border-slate-100 relative' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"
            >
                @if ($isActive)
                    <span class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-5 bg-indigo-600 rounded-r-full"></span>
                @endif
                <x-admin.icon name="{{ $item['icon'] }}" class="h-4 w-4 {{ $isActive ? 'text-indigo-600' : 'text-slate-400' }}" />
                <span>{{ $item['name'] }}</span>
            </a>
        @endforeach
    </nav>

    <div class="px-4">
        <button class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition-colors">
            <x-admin.icon name="settings" class="h-4 w-4 text-slate-400" />
            <span>Settings</span>
        </button>
    </div>
</aside>
