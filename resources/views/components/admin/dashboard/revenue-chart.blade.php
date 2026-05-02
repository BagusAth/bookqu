@props([
    'periods' => [],
    'series' => [],
    'activePeriod' => 'Monthly',
])

@php
    $maxValue = !empty($series) ? max($series) : 0;
@endphp

<div class="lg:col-span-2 bg-white border border-slate-200 rounded-xl p-6 shadow-sm flex flex-col">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h3 class="text-lg font-bold text-slate-900">Revenue Growth</h3>
            <p class="text-sm text-slate-500">Monthly earnings overview</p>
        </div>
        <div class="flex bg-slate-100 rounded-lg p-1">
            <button class="px-4 py-1.5 text-sm font-medium {{ $activePeriod === 'Weekly' ? 'bg-indigo-600 text-white shadow-sm rounded-md' : 'text-slate-600 rounded-md' }}">Weekly</button>
            <button class="px-4 py-1.5 text-sm font-medium {{ $activePeriod === 'Monthly' ? 'bg-indigo-600 text-white shadow-sm rounded-md' : 'text-slate-600 rounded-md' }}">Monthly</button>
        </div>
    </div>

    <div class="flex-1 min-h-[200px] flex flex-col justify-end">
        <div class="w-full flex items-end justify-between px-8 gap-2 h-44">
            @foreach ($periods as $index => $period)
                @php
                    $value = $series[$index] ?? 0;
                    $height = $maxValue > 0 ? intval(($value / $maxValue) * 100) : 0;
                @endphp
                <div class="flex flex-col items-center flex-1">
                    <div class="w-full max-w-[20px] bg-indigo-100 rounded-t-md overflow-hidden h-full flex items-end">
                        <div class="w-full bg-indigo-500" style="height: {{ $height }}%"></div>
                    </div>
                    <span class="mt-3 text-xs font-medium text-slate-400">{{ $period }}</span>
                </div>
            @endforeach
        </div>
    </div>
</div>
