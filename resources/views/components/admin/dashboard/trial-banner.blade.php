@props([
    'daysRemaining' => 0,
    'ctaText' => 'Upgrade Plan',
    'ctaUrl' => '#',
])

<div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 flex items-center justify-between">
    <div class="flex items-center gap-3 text-indigo-800 text-sm font-medium">
        <div class="bg-indigo-600 text-white rounded-full p-1">
            <x-admin.icon name="info" class="h-3.5 w-3.5" />
        </div>
        {{ $daysRemaining }}-day Free Trial remaining. Upgrade now to keep all premium features.
    </div>
    <a href="{{ $ctaUrl }}" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
        {{ $ctaText }}
    </a>
</div>
