@props([
    'periods' => [],
    'activePeriod' => 'Monthly',
])

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

    <div class="flex-1 min-h-[200px] flex items-end">
        <div class="w-full flex justify-between px-8 text-xs font-medium text-slate-400">
            @foreach ($periods as $period)
                <span>{{ $period }}</span>
            @endforeach
        </div>
    </div>
</div>
