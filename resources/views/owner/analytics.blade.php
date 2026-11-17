@extends('layouts.owner-layout')

@section('title', 'Laporan')

{{--
    Laporan (BQ-147, owner-portal.md §8.13, FR-ANALYTICS-007): one period filter shared by the tabs
    Ringkasan, Pendapatan, Okupansi, Layanan and Pelanggan, and a CSV export per tab. Figures come
    from App\Actions\Report\GetReport; below Medium the page is a preview without data (AC-LAP-03).
--}}

@php
    $rupiah = fn (float|int $value): string => 'Rp '.number_format((float) $value, 0, ',', '.');
    $percent = fn (float|int $value): string => number_format((float) $value, 1, ',', '.').'%';
    $card = 'bq-panel';
    $field = 'mt-1 block w-full bq-input';
    $tab = $report['tab'];
    $period = $report['period'];
    $summary = $report['summary'];
    $comparison = $report['comparison'];
    $number = fn (int|float $value): string => number_format($value, 0, ',', '.');
    $exportUrl = route('owner.analytics.export', ['tab' => $tab] + $period['query']);
    $heatClass = function (int $count, int $max): string {
        if ($count === 0 || $max === 0) {
            return 'bg-bq-background text-bq-text-muted';
        }
        $level = (int) ceil($count / $max * 4);

        return match ($level) {
            1 => 'bg-bq-primary/20 text-bq-text',
            2 => 'bg-bq-primary/40 text-bq-text',
            3 => 'bg-bq-primary/70 text-white',
            default => 'bg-bq-primary text-white',
        };
    };
@endphp

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <x-owner.page-header title="Laporan" purpose="Bagaimana bisnis Anda berjalan, dan apa yang bisa diperbaiki.">
        @unless ($report['locked'])
            <x-slot:action>
                <a href="{{ $exportUrl }}" class="bq-btn bq-btn-secondary">Ekspor CSV</a>
            </x-slot:action>
        @endunless
    </x-owner.page-header>

    <form method="GET" action="{{ route('owner.analytics') }}" class="{{ $card }} flex flex-wrap items-end gap-3 p-4" x-data="{ periode: @js($period['key']) }">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <div class="w-full sm:w-56">
            <label for="report-period" class="block text-sm font-medium text-bq-text">Periode</label>
            <select id="report-period" name="periode" x-model="periode" class="{{ $field }}">
                @foreach (\App\Support\ReportPeriod::PRESETS as $key => $label)
                    <option value="{{ $key }}" @selected($period['key'] === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="grid w-full gap-3 min-[420px]:grid-cols-2 sm:w-auto" x-show="periode === 'kustom'" @if ($period['key'] !== 'kustom') style="display: none" @endif>
            <div class="flex-1">
                <label for="report-from" class="block text-sm font-medium text-bq-text">Dari</label>
                <input id="report-from" type="date" name="dari" value="{{ $period['from'] }}" class="{{ $field }}" x-bind:disabled="periode !== 'kustom'">
            </div>
            <div class="flex-1">
                <label for="report-to" class="block text-sm font-medium text-bq-text">Sampai</label>
                <input id="report-to" type="date" name="sampai" value="{{ $period['to'] }}" class="{{ $field }}" x-bind:disabled="periode !== 'kustom'">
            </div>
        </div>
        <button type="submit" class="bq-btn bq-btn-primary">Terapkan</button>
        <p class="w-full text-xs text-bq-text-muted">{{ $period['label'] }} · {{ \Carbon\Carbon::parse($period['from'])->translatedFormat('j M Y') }} – {{ \Carbon\Carbon::parse($period['to'])->translatedFormat('j M Y') }}. Rentang kustom paling lama 366 hari.</p>
    </form>

    <x-owner.tabs :tabs="$report['tabs']" :active="$tab" label="Bagian laporan" />

    @if ($report['locked'])
        <x-owner.upgrade-prompt feature="Laporan" plan="Medium" description="Lihat pendapatan, okupansi, layanan terlaris dan pelanggan baru per periode, lalu unduh setiap bagian sebagai CSV." />
        {{-- A sample only: no figure of the business is read below Medium. --}}
        <div class="pointer-events-none select-none blur-sm" aria-hidden="true">
            <div class="bq-panel grid grid-cols-2 lg:grid-cols-4 gap-px overflow-hidden bg-bq-border">
                @foreach (['Pendapatan bersih' => 'Rp 4.250.000', 'Booking' => '86', 'Pelanggan baru' => '23', 'Tingkat selesai' => '91,0%'] as $label => $value)
                    <div class="min-w-0 bg-bq-surface p-4 sm:p-5">
                        <p class="text-xs font-medium text-bq-text-muted">{{ $label }}</p>
                        <p class="bq-num mt-1 text-2xl font-bold text-bq-ink">{{ $value }}</p>
                    </div>
                @endforeach
            </div>
            <div class="{{ $card }} mt-3 h-40"></div>
        </div>
    @else
        <div class="bq-panel px-4 py-3">
            <p class="text-bq-body font-semibold">{{ $report['conclusion'] }}</p>
            <p class="mt-1 text-bq-small text-bq-text-muted">Dibanding {{ $comparison['label'] }}</p>
        </div>
        @switch($tab)
            @case('pendapatan')
                @php $revenue = $report['revenue']; $maxNet = max([1, ...array_map(fn ($point) => abs($point['net']), $revenue['series'])]); @endphp
                <section aria-labelledby="revenue-title" class="space-y-4">
                    <h2 id="revenue-title" class="sr-only">Pendapatan</h2>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-ui.stat hero label="Pendapatan bersih" :value="$rupiah($revenue['net'])"
                            :context="'Pembayaran berhasil dikurangi refund · sebelumnya '.$rupiah($comparison['net'])" />
                        <x-ui.stat label="Pendapatan kotor" :value="$rupiah($revenue['gross'])"
                            :context="'Menurut tanggal bayar · sebelumnya '.$rupiah($comparison['gross'])" />
                    </div>
                    <dl class="bq-panel grid grid-cols-2 gap-4 p-4 sm:grid-cols-3">
                        @foreach (['Online' => $revenue['online'], 'Dicatat kasir' => $revenue['walkIn'], 'Refund' => $revenue['refunds']] as $label => $value)
                            <div><dt class="text-bq-small text-bq-text-muted">{{ $label }}</dt><dd class="bq-num font-semibold">{{ $rupiah($value) }}</dd></div>
                        @endforeach
                    </dl>

                    <div class="{{ $card }} overflow-hidden">
                        <h3 class="border-b border-bq-border px-4 py-3 text-sm font-semibold text-bq-text">Pendapatan bersih per {{ $revenue['daily'] ? 'hari' : 'bulan' }}</h3>
                        <div class="px-4 pb-3" aria-hidden="true">
                            <div class="flex h-24 items-end gap-0.5 border-b border-bq-border">
                                @foreach ($revenue['series'] as $point)
                                    <span class="min-w-0 flex-1 rounded-t-sm {{ $point['net'] < 0 ? 'bg-bq-danger' : 'bg-bq-primary' }}"
                                        style="height: {{ max(2, round(abs($point['net']) / $maxNet * 100)) }}%"
                                        title="{{ $point['label'] }} · {{ $rupiah($point['net']) }}"></span>
                                @endforeach
                            </div>
                            <div class="mt-1 flex justify-between text-bq-small text-bq-text-muted">
                                <span>{{ $revenue['series'][0]['label'] ?? '' }}</span><span>{{ $revenue['series'][array_key_last($revenue['series'])]['label'] ?? '' }}</span>
                            </div>
                        </div>
                        <details class="border-t border-bq-border">
                            <summary class="cursor-pointer px-4 py-3 text-bq-small font-semibold">Rincian pendapatan</summary>
                            <div class="max-h-96 overflow-y-auto">
                            <table class="w-full text-sm">
                                <caption class="sr-only">Pendapatan kotor, refund dan bersih per {{ $revenue['daily'] ? 'hari' : 'bulan' }}</caption>
                                <thead class="sticky top-0 bg-bq-surface text-left text-xs text-bq-text-muted">
                                    <tr><th scope="col" class="px-4 py-2 font-medium">{{ $revenue['daily'] ? 'Hari' : 'Bulan' }}</th><th scope="col" class="px-4 py-2 font-medium">Bersih</th><th scope="col" class="hidden px-4 py-2 text-right font-medium sm:table-cell">Kotor</th><th scope="col" class="hidden px-4 py-2 text-right font-medium sm:table-cell">Refund</th></tr>
                                </thead>
                                <tbody class="divide-y divide-bq-border">
                                    @foreach ($revenue['series'] as $point)
                                        <tr>
                                            <th scope="row" class="whitespace-nowrap px-4 py-2 text-left font-normal text-bq-text-muted">{{ $point['label'] }}</th>
                                            <td class="w-full px-4 py-2">
                                                <div class="flex items-center gap-2">
                                                    <span class="h-2 rounded-full bg-bq-primary" style="width: {{ round(max(0, $point['net']) / $maxNet * 100, 1) }}%; min-width: {{ $point['net'] > 0 ? '2px' : '0' }}" aria-hidden="true"></span>
                                                    <span class="whitespace-nowrap text-bq-text">{{ $rupiah($point['net']) }}</span>
                                                </div>
                                            </td>
                                            <td class="hidden whitespace-nowrap px-4 py-2 text-right text-bq-text-muted sm:table-cell">{{ $rupiah($point['gross']) }}</td>
                                            <td class="hidden whitespace-nowrap px-4 py-2 text-right text-bq-text-muted sm:table-cell">{{ $rupiah($point['refunds']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            </div>
                        </details>
                    </div>

                    <div class="{{ $card }} overflow-hidden">
                        <h3 class="border-b border-bq-border px-4 py-3 text-sm font-semibold text-bq-text">Per layanan</h3>
                        @if ($revenue['perService'] === [])
                            <p class="px-4 py-6 text-center text-sm text-bq-text-muted">Belum ada pembayaran pada periode ini.</p>
                        @else
                            <table class="w-full text-sm">
                                <caption class="sr-only">Pendapatan per layanan</caption>
                                <thead class="text-left text-xs text-bq-text-muted">
                                    <tr><th scope="col" class="px-4 py-2 font-medium">Layanan</th><th scope="col" class="hidden px-4 py-2 text-right font-medium sm:table-cell">Kotor</th><th scope="col" class="hidden px-4 py-2 text-right font-medium sm:table-cell">Refund</th><th scope="col" class="px-4 py-2 text-right font-medium">Bersih</th></tr>
                                </thead>
                                <tbody class="divide-y divide-bq-border">
                                    @foreach ($revenue['perService'] as $row)
                                        <tr>
                                            <th scope="row" class="px-4 py-2 text-left font-normal text-bq-text">{{ $row['name'] }}</th>
                                            <td class="hidden whitespace-nowrap px-4 py-2 text-right text-bq-text-muted sm:table-cell">{{ $rupiah($row['gross']) }}</td>
                                            <td class="hidden whitespace-nowrap px-4 py-2 text-right text-bq-text-muted sm:table-cell">{{ $rupiah($row['refunds']) }}</td>
                                            <td class="whitespace-nowrap px-4 py-2 text-right font-medium text-bq-text">{{ $rupiah($row['net']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </section>
                @break

            @case('okupansi')
                @php $occupancy = $report['occupancy']; $heatmap = $occupancy['heatmap']; @endphp
                <section aria-labelledby="occupancy-title" class="space-y-4">
                    <h2 id="occupancy-title" class="sr-only">Okupansi</h2>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-ui.stat hero label="Tingkat okupansi" :value="$percent($occupancy['rate'])"
                            :context="'Kursi terisi dari total kapasitas · sebelumnya '.$percent($comparison['rate'])" />
                        <x-ui.stat label="Slot terisi" :value="$number($occupancy['booked'])"
                            :context="'Menurut tanggal sesi · sebelumnya '.$number($comparison['booked'])"
                            :supporting="['Kursi terisi / kapasitas' => $number($occupancy['seats']).' / '.$number($occupancy['capacity']), 'Slot kosong' => $number($occupancy['free'])]" />
                    </div>

                    <div class="{{ $card }} p-4">
                        <h3 class="text-sm font-semibold text-bq-text">Jam dan hari ramai</h3>
                        @if ($occupancy['total'] === 0)
                            <p class="mt-2 text-sm text-bq-text-muted">Belum ada slot pada periode ini.</p>
                        @else
                            <p class="mt-1 text-sm text-bq-text-muted">
                                @if ($occupancy['busiestDay'])
                                    Hari paling ramai: {{ $occupancy['busiestDay'] }}. Jam paling ramai: {{ $occupancy['busiestHour'] }}. Angka di kotak adalah jumlah slot terisi.
                                @else
                                    Belum ada slot terisi pada periode ini.
                                @endif
                            </p>
                            <div class="mt-3 overflow-x-auto">
                                <table class="text-xs">
                                    <caption class="sr-only">Jumlah slot terisi per hari dan jam</caption>
                                    <thead>
                                        <tr>
                                            <td></td>
                                            @foreach ($heatmap['hours'] as $hour)
                                                <th scope="col" class="px-1 pb-1 font-medium text-bq-text-muted">{{ sprintf('%02d', $hour) }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($heatmap['days'] as $day => $label)
                                            <tr>
                                                <th scope="row" class="pr-2 text-left font-medium text-bq-text-muted">{{ $label }}</th>
                                                @foreach ($heatmap['hours'] as $hour)
                                                    @php $count = $heatmap['cells'][$day][$hour]; @endphp
                                                    <td class="p-0.5"><span class="flex h-8 w-8 items-center justify-center rounded {{ $heatClass($count, $heatmap['max']) }}">{{ $count }}</span></td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                    @if ($occupancy['units'] !== null)
                        <div class="{{ $card }} p-4">
                            <h3 class="text-sm font-semibold text-bq-text">Pemanfaatan per unit</h3>
                            <ul class="mt-2 divide-y divide-bq-border text-sm">
                                @forelse ($occupancy['units'] as $unit)
                                    <li class="flex flex-wrap justify-between gap-2 py-2">
                                        <span>{{ $unit['name'] }}</span>
                                        <span class="text-bq-text-muted">{{ $unit['usedMinutes'] }} / {{ $unit['offeredMinutes'] }} menit · {{ $percent($unit['rate']) }}</span>
                                    </li>
                                @empty
                                    <li class="py-2 text-bq-text-muted">Belum ada unit aktif.</li>
                                @endforelse
                            </ul>
                        </div>
                    @endif
                </section>
                @break

            @case('layanan')
                @php $services = $report['services']; $maxBookings = max([1, ...array_column($services, 'bookings')]); @endphp
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-ui.stat hero label="Pendapatan bersih" :value="$rupiah($summary['revenue'])" :context="'Sebelumnya '.$rupiah($comparison['net'])" />
                    <x-ui.stat label="Booking" :value="$number($summary['bookings'])" :context="'Menurut tanggal sesi · sebelumnya '.$number($comparison['bookings'])" />
                </div>
                <section aria-labelledby="services-title" class="{{ $card }} overflow-hidden">
                    <h2 id="services-title" class="border-b border-bq-border px-4 py-3 text-sm font-semibold text-bq-text">Layanan menurut booking dan pendapatan</h2>
                    @if ($services === [])
                        <p class="px-4 py-6 text-center text-sm text-bq-text-muted">Belum ada booking atau pembayaran pada periode ini.</p>
                    @else
                        <table class="w-full text-sm">
                            <caption class="sr-only">Peringkat layanan</caption>
                            <thead class="text-left text-xs text-bq-text-muted">
                                <tr><th scope="col" class="px-4 py-2 font-medium">Layanan</th><th scope="col" class="px-4 py-2 font-medium">Booking</th><th scope="col" class="px-4 py-2 text-right font-medium">Pendapatan bersih</th></tr>
                            </thead>
                            <tbody class="divide-y divide-bq-border">
                                @foreach ($services as $row)
                                    <tr>
                                        <th scope="row" class="px-4 py-2 text-left font-normal text-bq-text">{{ $row['name'] }}</th>
                                        <td class="w-1/2 px-4 py-2">
                                            <div class="flex items-center gap-2">
                                                <span class="h-2 rounded-full bg-bq-primary" style="width: {{ round($row['bookings'] / $maxBookings * 100, 1) }}%" aria-hidden="true"></span>
                                                <span class="text-bq-text">{{ $row['bookings'] }}</span>
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-2 text-right text-bq-text">{{ $rupiah($row['revenue']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </section>
                @break

            @case('pelanggan')
                @php $customers = $report['customers']; $newShare = $customers['active'] > 0 ? round($customers['new'] / $customers['active'] * 100, 1) : 0; @endphp
                <section aria-labelledby="customers-title" class="space-y-4">
                    <h2 id="customers-title" class="sr-only">Pelanggan</h2>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-ui.stat hero label="Pelanggan baru" :value="$number($customers['new'])" :context="'Sebelumnya '.$number($comparison['new'])" />
                        <x-ui.stat label="Pelanggan kembali" :value="$number($customers['returning'])" :context="'Sebelumnya '.$number($comparison['returning'])"
                            :supporting="['Pelanggan aktif' => $number($customers['active']), 'Rata-rata booking sebelum sesi' => $customers['advanceDays'] === null ? '—' : number_format($customers['advanceDays'], 1, ',', '.').' hari']" />
                    </div>
                    <div class="{{ $card }} p-4">
                        <h3 class="text-sm font-semibold text-bq-text">Baru dan kembali</h3>
                        @if ($customers['active'] === 0)
                            <p class="mt-2 text-sm text-bq-text-muted">Belum ada pelanggan yang booking pada periode ini.</p>
                        @else
                            <div class="mt-3 flex h-3 overflow-hidden rounded-full bg-bq-background" aria-hidden="true">
                                <span class="bg-bq-primary" style="width: {{ $newShare }}%"></span>
                            </div>
                            <p class="mt-2 text-sm text-bq-text-muted">{{ $percent($newShare) }} pelanggan pada periode ini baru pertama kali booking.</p>
                        @endif
                    </div>
                    @if ($customers['promos'] !== null)
                        <div class="{{ $card }} p-4">
                            <h3 class="text-sm font-semibold text-bq-text">Pemakaian promo</h3>
                            <ul class="mt-2 divide-y divide-bq-border text-sm">
                                @forelse ($customers['promos'] as $promo)
                                    <li class="flex flex-wrap justify-between gap-2 py-2">
                                        <span>{{ $promo['customer'] }} · {{ $promo['code'] }}</span>
                                        <span class="text-bq-text-muted">{{ $promo['uses'] }} booking · Diskon {{ $rupiah($promo['discount']) }}</span>
                                    </li>
                                @empty
                                    <li class="py-2 text-bq-text-muted">Belum ada promo terpakai pada periode ini.</li>
                                @endforelse
                            </ul>
                        </div>
                    @endif
                    <section class="bq-panel" aria-labelledby="report-unpaid">
                        <div class="px-4 py-3">
                            <h3 id="report-unpaid" class="font-semibold">Belum jadi membayar</h3>
                            <p class="mt-1 text-bq-small text-bq-text-muted">Sudah memilih slot tetapi tidak membayar.</p>
                        </div>
                        <ul class="divide-y divide-bq-border">
                            @forelse ($customers['unpaid'] as $booking)
                                <li class="flex items-center justify-between gap-3 border-t border-bq-border px-4 py-3">
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold" title="{{ $booking['name'] }}">{{ $booking['name'] }}</p>
                                        @php $details = $booking['service'].' · '.$booking['date'].' · Pembayaran tidak selesai'; @endphp
                                        <p class="truncate text-bq-small text-bq-text-muted" title="{{ $details }}">{{ $details }}</p>
                                    </div>
                                    @if ($booking['whatsAppUrl'])
                                        <a href="{{ $booking['whatsAppUrl'] }}" rel="noopener" target="_blank" class="bq-btn bq-btn-secondary bq-btn-sm shrink-0"
                                            aria-label="Buka WhatsApp untuk {{ $booking['name'] }}">WhatsApp</a>
                                    @endif
                                </li>
                            @empty
                                <li class="border-t border-bq-border px-4 py-3 text-bq-small text-bq-text-muted">Tidak ada booking yang belum jadi membayar.</li>
                            @endforelse
                        </ul>
                    </section>
                </section>
                @break

            @default
                <section aria-labelledby="summary-title" class="space-y-3">
                    <h2 id="summary-title" class="sr-only">Ringkasan</h2>
                    @php
                        $figures = [
                            'Pelanggan baru' => number_format($summary['newCustomers'], 0, ',', '.'),
                            'Tingkat pembatalan' => $percent($summary['cancellationRate']),
                            'Tingkat selesai' => $percent($summary['completionRate']),
                        ];
                        if ($summary['noShowRate'] !== null) {
                            $figures['Tingkat tidak datang'] = $percent($summary['noShowRate']);
                        }
                        $figures['Rata-rata rating'] = $summary['averageRating'] !== null ? number_format($summary['averageRating'], 1, ',', '.').' dari '.$summary['reviewCount'].' ulasan' : 'Belum ada ulasan';
                        $figures['Layanan terlaris'] = $summary['topService'] ?? 'Belum ada booking';
                        if ($summary['showUnits']) {
                            $figures['Unit paling sering dipakai'] = $summary['topUnit'] ?? 'Belum ada pemakaian';
                        }
                    @endphp
                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-ui.stat hero label="Pendapatan bersih" :value="$rupiah($summary['revenue'])"
                            :context="'Menurut tanggal bayar · sebelumnya '.$rupiah($comparison['net'])" />
                        <x-ui.stat label="Booking" :value="$number($summary['bookings'])"
                            :context="'Menurut tanggal sesi · sebelumnya '.$number($comparison['bookings'])" />
                    </div>
                    <div class="bq-panel grid grid-cols-2 lg:grid-cols-4 gap-px overflow-hidden bg-bq-border">
                        @foreach ($figures as $label => $value)
                            <div class="min-w-0 bg-bq-surface p-4 sm:p-5">
                                <p class="text-xs font-medium text-bq-text-muted">{{ $label }}</p>
                                <p class="mt-1 text-lg font-semibold text-bq-text break-words">{{ $value }}</p>
                            </div>
                        @endforeach
                    </div>
                    <p class="text-xs text-bq-text-muted">Booking dihitung menurut tanggal layanan; booking beberapa slot dihitung satu. Pendapatan dihitung dari pembayaran yang berhasil dikurangi refund.</p>
                </section>
        @endswitch
    @endif
</div>
@endsection
