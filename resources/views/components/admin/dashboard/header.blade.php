@props([
    'userName' => 'Admin',
    'userSubtitle' => 'Here\'s what\'s happening today.',
    'avatarUrl' => null,
])

<header class="flex items-center justify-between">
    <div>
        <h2 class="text-2xl font-semibold text-slate-900">Welcome back, {{ $userName }}</h2>
        <p class="text-slate-500 text-sm mt-1">{{ $userSubtitle }}</p>
    </div>
    <div class="flex items-center gap-4">
        <button class="relative p-2 rounded-full bg-slate-100 text-slate-600 hover:bg-slate-200 transition-colors">
            <x-admin.icon name="bell" class="h-5 w-5" />
            <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full border-2 border-slate-100"></span>
        </button>
        <div class="w-10 h-10 rounded-full border-2 border-white shadow-sm overflow-hidden bg-slate-200">
            @if ($avatarUrl)
                <img src="{{ $avatarUrl }}" alt="User profile" class="w-full h-full object-cover" />
            @else
                <div class="w-full h-full flex items-center justify-center text-sm font-semibold text-slate-600">
                    {{ strtoupper(substr($userName, 0, 2)) }}
                </div>
            @endif
        </div>
    </div>
</header>
