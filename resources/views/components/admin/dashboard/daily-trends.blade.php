@props([
    'items' => [],
    'ctaText' => 'View Full Report',
    'ctaUrl' => '#',
])

<div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm flex flex-col">
    <h3 class="text-lg font-bold text-slate-900 mb-6">Daily Trends</h3>

    <div class="space-y-6 flex-1">
        @forelse ($items as $item)
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full {{ $item['icon_bg'] ?? 'bg-slate-50' }} flex items-center justify-center {{ $item['icon_color'] ?? 'text-slate-500' }}">
                        <x-admin.icon name="{{ $item['icon'] ?? 'trending' }}" class="h-4 w-4" />
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-slate-900">{{ $item['title'] ?? '-' }}</h4>
                        <p class="text-xs text-slate-500">{{ $item['subtitle'] ?? '' }}</p>
                    </div>
                </div>
                <span class="font-bold text-slate-900 text-sm">{{ $item['value'] ?? '-' }}</span>
            </div>
        @empty
            <div class="text-sm text-slate-500">Belum ada data tren.</div>
        @endforelse
    </div>

    <div class="mt-8 pt-4 flex justify-center border-t border-slate-100">
        <a href="{{ $ctaUrl }}" class="text-indigo-600 text-sm font-medium hover:text-indigo-700">
            {{ $ctaText }}
        </a>
    </div>
</div>
