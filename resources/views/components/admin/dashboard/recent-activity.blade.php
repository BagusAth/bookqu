@props([
    'items' => [],
    'ctaText' => 'View All Activity',
    'ctaUrl' => '#',
])

@php
    $badgeMap = [
        'confirmed' => 'bg-emerald-100 text-emerald-700',
        'pending' => 'bg-amber-100 text-amber-700',
        'cancelled' => 'bg-rose-100 text-rose-700',
    ];
@endphp

<div>
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-bold text-slate-900">Recent Activity</h3>
        <a href="{{ $ctaUrl }}" class="text-slate-500 text-sm font-medium hover:text-slate-800 flex items-center gap-1">
            {{ $ctaText }} <x-admin.icon name="chevron-right" class="h-4 w-4" />
        </a>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="px-6 py-4 font-bold text-slate-500 text-xs tracking-wider">PROGRAM</th>
                    <th class="px-6 py-4 font-bold text-slate-500 text-xs tracking-wider">STATUS</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($items as $item)
                    @php
                        $status = $item['status'] ?? 'pending';
                        $badgeClass = $badgeMap[$status] ?? 'bg-slate-100 text-slate-600';
                    @endphp
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 font-medium text-slate-600">{{ $item['program'] ?? '-' }}</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $badgeClass }}">
                                {{ strtoupper($status) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="px-6 py-6 text-center text-slate-500">Belum ada aktivitas terbaru.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
