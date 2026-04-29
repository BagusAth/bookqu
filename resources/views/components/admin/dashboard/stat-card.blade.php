@props([
    'title' => 'Metric',
    'value' => '0',
    'trend' => null,
    'icon' => 'calendar-days',
    'iconBg' => 'bg-slate-100',
    'iconColor' => 'text-slate-600',
    'trendVariant' => 'success',
])

@php
    $trendClass = $trendVariant === 'success'
        ? 'bg-emerald-100 text-emerald-700'
        : 'bg-slate-100 text-slate-600';
@endphp

<div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
    <div class="flex items-start justify-between mb-4">
        <div class="p-2 {{ $iconBg }} {{ $iconColor }} rounded-lg">
            <x-admin.icon name="{{ $icon }}" class="h-5 w-5" />
        </div>
        @if ($trend)
            <span class="{{ $trendClass }} text-xs font-semibold px-2 py-0.5 rounded-full flex items-center gap-1">
                {{ $trend }}
            </span>
        @endif
    </div>
    <div>
        <p class="text-xs font-bold tracking-wider text-slate-500 mb-1">{{ strtoupper($title) }}</p>
        <h3 class="text-2xl font-bold text-slate-900">{{ $value }}</h3>
    </div>
</div>
