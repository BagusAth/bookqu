@extends('layouts.owner-layout')

@section('title', 'Laporan')

{{--
    Laporan (BQ-147, owner-portal.md §8.13, FR-ANALYTICS-007): one period filter shared by the tabs
    Ringkasan, Pendapatan, Okupansi, Layanan and Pelanggan, and a CSV export per tab. Figures come
    from App\Actions\Report\GetReport; below Medium the page is a preview without data (AC-LAP-03).
    BQ-162: laid out as the Papan Jadwal prototype (#/laporan): period segments, a conclusion line,
    figure panels with the change against the previous period.
--}}

@php
    $rupiah = fn (float|int $value): string => \App\Support\Rupiah::format($value);
    $percent = fn (float|int $value): string => number_format((float) $value, 1, ',', '.').'%';
    $tab = $report['tab'];
    $period = $report['period'];
    $summary = $report['summary'];
    $comparison = $report['comparison'];
    $number = fn (int|float $value): string => number_format($value, 0, ',', '.');
    // Change against the previous period in whole percent; null when there is nothing to compare with.
    $delta = fn (int|float|null $current, int|float|null $previous): ?int => $previous === null || (float) $previous == 0.0 ? null : (int) round(((float) $current - (float) $previous) / abs((float) $previous) * 100);
    $exportUrl = route('owner.analytics.export', ['tab' => $tab] + $period['query']);
    $periodLabel = \Carbon\Carbon::parse($period['from'])->locale('id')->translatedFormat('j M').'–'.\Carbon\Carbon::parse($period['to'])->locale('id')->translatedFormat('j M Y');
    $heatLevel = fn (int $count, int $max): int => $count === 0 || $max === 0 ? 0 : max(1, min(4, (int) ceil($count / $max * 4)));
    $presetOptions = collect(\App\Support\ReportPeriod::PRESETS)->except(['6-bulan'])
        ->map(fn ($label, $key) => ['label' => $key === 'kustom' ? 'Pilih' : str_replace(' terakhir', '', $label), 'href' => route('owner.analytics', ['tab' => $tab, 'periode' => $key])])->all();
@endphp

@section('content')
<div class="mx-auto max-w-7xl space-y-5">
    <x-owner.page-header title="Laporan" purpose="Bagaimana bisnis Anda berjalan, dan apa yang bisa diperbaiki." />

    @if ($report['locked'])
        <x-owner.tabs :tabs="$report['tabs']" :active="$tab" label="Bagian laporan" />
        <x-owner.upgrade-prompt feature="Laporan" plan="Medium" description="Lihat pendapatan, jam ramai dan layanan terlaris, dibanding bulan lalu." />
        {{-- A sample only: no figure of the business is read below Medium. --}}
        <div class="pointer-events-none select-none blur-sm" aria-hidden="true">
            <x-ui.panel><x-ui.figures value="Rp0.000.000" label="pendapatan bersih" :more="[['value' => '00', 'label' => 'booking'], ['value' => '00%', 'label' => 'slot terisi']]" /></x-ui.panel>
        </div>
    @else
        <div class="bq-toolbar">
            <x-ui.segmented label="Periode" :selected="$period['key']" :options="$presetOptions" />
            <span class="text-bq-small text-bq-text-muted">{{ $periodLabel }}, dibanding {{ $comparison['label'] }}@if ($comparison['current'] !== []) (perubahan dihitung sampai tanggal yang sama)@endif</span>
            <a href="{{ $exportUrl }}" class="bq-btn bq-btn-secondary bq-btn-sm ml-auto"><x-owner.icon name="download" class="h-4 w-4" />Ekspor CSV</a>
            @if ($period['key'] === 'kustom')
            <form id="pilih-periode" method="GET" action="{{ route('owner.analytics') }}" class="flex w-full flex-wrap items-end gap-2">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <input type="hidden" name="periode" value="kustom">
                <label class="grid gap-1 text-bq-small">Dari <input type="date" name="dari" value="{{ $period['from'] }}" class="bq-input"></label>
                <label class="grid gap-1 text-bq-small">Sampai <input type="date" name="sampai" value="{{ $period['to'] }}" class="bq-input"></label>
                <x-ui.button type="submit" size="sm" variant="primary">Terapkan</x-ui.button>
                <span class="text-bq-small text-bq-text-muted">Paling lama 366 hari.</span>
            </form>
            @endif
        </div>

        <x-owner.tabs :tabs="$report['tabs']" :active="$tab" label="Bagian laporan" />

        @if ($report['conclusion'])
            <p class="flex items-start gap-2 text-bq-body font-semibold text-bq-ink"><x-owner.icon name="info" class="mt-0.5 h-[18px] w-[18px] shrink-0 text-bq-primary" />{{ $report['conclusion'] }}</p>
        @endif

        @switch($tab)
            @case('pendapatan')
                @php $revenue = $report['revenue']; $maxNet = max([1, ...array_map(fn ($point) => abs($point['net']), $revenue['series'])]); @endphp
                <section aria-labelledby="revenue-title" class="space-y-4">
                    <h2 id="revenue-title" class="sr-only">Pendapatan</h2>
                    <x-ui.panel>
                        <x-ui.figures :value="$rupiah($revenue['net'])" label="pendapatan bersih" :delta="$delta($comparison['current']['net'] ?? $revenue['net'], $comparison['net'] ?? null)"
                            :context="'Dari pembayaran berhasil dikurangi refund, menurut tanggal bayar · kotor '.$rupiah($revenue['gross'])"
                            :more="[
                                ['value' => $rupiah($revenue['online']), 'label' => 'online'],
                                ['value' => $rupiah($revenue['walkIn']), 'label' => 'dicatat kasir'],
                                ['value' => ($revenue['refunds'] > 0 ? '−' : '').$rupiah($revenue['refunds']), 'label' => 'refund'],
                            ]" />
                        <div class="mt-5" aria-hidden="true">
                            <div class="flex h-24 items-end gap-0.5 border-b border-bq-border">
                                @foreach ($revenue['series'] as $point)
                                    <span @class(['min-w-0 flex-1 rounded-t-sm', 'bg-bq-danger' => $point['net'] < 0, 'bg-bq-accent' => $point['net'] >= 0 && $loop->last, 'bg-bq-primary' => $point['net'] >= 0 && ! $loop->last])
                                        style="height: {{ max(2, round(abs($point['net']) / $maxNet * 100)) }}%"
                                        title="{{ $point['label'] }} · {{ $rupiah($point['net']) }}"></span>
                                @endforeach
                            </div>
                            <div class="mt-1 flex justify-between text-bq-small text-bq-text-muted">
                                <span>{{ $revenue['series'][0]['label'] ?? '' }}</span><span>{{ $revenue['series'][array_key_last($revenue['series'])]['label'] ?? '' }}</span>
                            </div>
                        </div>
                    </x-ui.panel>
                    <x-ui.panel title="Per layanan" description="Pendapatan bersih per layanan, menurut tanggal bayar.">
                        @if ($revenue['perService'] === [])
                            <p class="text-bq-small text-bq-text-muted">Belum ada pembayaran pada periode ini.</p>
                        @else
                            @php $maxService = max([1, ...array_map(fn ($row) => abs($row['net']), $revenue['perService'])]); @endphp
                            <div class="grid gap-3">
                                @foreach ($revenue['perService'] as $row)
                                    <div class="grid grid-cols-[minmax(0,1.4fr)_minmax(0,2fr)_auto] items-center gap-3 text-bq-body">
                                        <span class="truncate" title="{{ $row['name'] }}">{{ $row['name'] }}</span>
                                        <span class="h-2 rounded-full bg-bq-surface-2"><span class="block h-full rounded-full bg-bq-primary" style="width: {{ round(max(0, $row['net']) / $maxService * 100, 1) }}%"></span></span>
                                        <span class="bq-num font-semibold whitespace-nowrap" title="Kotor {{ $rupiah($row['gross']) }} · refund {{ $rupiah($row['refunds']) }}">{{ $rupiah($row['net']) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </x-ui.panel>
                </section>
                @break

            @case('okupansi')
                @php $occupancy = $report['occupancy']; $heatmap = $occupancy['heatmap']; @endphp
                <section aria-labelledby="occupancy-title" class="space-y-4">
                    <h2 id="occupancy-title" class="sr-only">Okupansi</h2>
                    <x-ui.panel>
                        <x-ui.figures :value="$percent($occupancy['rate'])" label="slot terisi" :delta="$delta($comparison['current']['rate'] ?? $occupancy['rate'], $comparison['rate'] ?? null)"
                            :context="$number($occupancy['seats']).' dari '.$number($occupancy['capacity']).' kursi, menurut tanggal sesi'"
                            :more="[
                                ['value' => $number($occupancy['booked']), 'label' => 'slot terisi'],
                                ['value' => $number($occupancy['free']), 'label' => 'slot kosong'],
                            ]" />
                    </x-ui.panel>

                    <x-ui.panel title="Peta jam ramai" description="Makin gelap, makin banyak booking. Arahkan kursor ke kotak untuk melihat angkanya.">
                        @if ($occupancy['total'] === 0)
                            <p class="text-bq-small text-bq-text-muted">Belum ada slot pada periode ini.</p>
                        @else
                            <div class="overflow-x-auto">
                                <table class="w-full min-w-[480px] border-separate border-spacing-1 text-bq-small">
                                    <caption class="sr-only">Jumlah slot terisi per hari dan jam</caption>
                                    <thead>
                                        <tr>
                                            <td class="w-10"></td>
                                            @foreach ($heatmap['hours'] as $hour)
                                                <th scope="col" class="font-bq-display font-semibold text-bq-text-muted">{{ sprintf('%02d', $hour) }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($heatmap['days'] as $day => $label)
                                            <tr>
                                                <th scope="row" class="pr-1 text-left font-medium text-bq-text-muted">{{ $label }}</th>
                                                @foreach ($heatmap['hours'] as $hour)
                                                    @php $count = $heatmap['cells'][$day][$hour]; $level = $heatLevel($count, $heatmap['max']); @endphp
                                                    <td @class(['h-[22px] rounded', 'bg-bq-surface-2' => $level === 0, 'bg-bq-heat-1' => $level === 1, 'bg-bq-heat-2' => $level === 2, 'bg-bq-heat-3' => $level === 3, 'bg-bq-primary' => $level === 4])
                                                        title="{{ $label }} {{ sprintf('%02d.00', $hour) }}: {{ $count }} slot terisi"><span class="sr-only">{{ $count }}</span></td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3 flex flex-wrap items-center gap-2 text-bq-small text-bq-text-muted" aria-hidden="true">
                                <span>Sepi</span>
                                @foreach (['bg-bq-surface-2', 'bg-bq-heat-1', 'bg-bq-heat-2', 'bg-bq-heat-3', 'bg-bq-primary'] as $swatch)<i class="inline-block h-3 w-5 rounded-sm {{ $swatch }}"></i>@endforeach
                                <span>Ramai</span>
                            </div>
                            @if (($occupancy['quietestDay'] ?? null) && \Illuminate\Support\Facades\Route::has('owner.promos'))
                                <p class="mt-4 flex flex-wrap items-center gap-2 text-bq-small text-bq-text-muted">
                                    <span>Paling sepi: {{ $occupancy['quietestDay'] }}, sekitar pukul {{ $occupancy['quietestHour'] }}.</span>
                                    <a href="{{ route('owner.promos', ['buat' => 1]) }}" class="bq-btn bq-btn-secondary bq-btn-sm"><x-owner.icon name="tag" class="h-4 w-4" />Buat promo untuk jam sepi</a>
                                </p>
                            @endif
                        @endif
                    </x-ui.panel>
                    @if ($occupancy['units'] !== null)
                        <x-ui.panel title="Pemakaian unit">
                            @forelse ($occupancy['units'] as $unit)
                                <div class="grid grid-cols-[minmax(0,1.4fr)_minmax(0,2fr)_auto] items-center gap-3 py-1.5 text-bq-body">
                                    <span class="truncate" title="{{ $unit['name'] }}">{{ $unit['name'] }}</span>
                                    <span class="h-2 rounded-full bg-bq-surface-2"><span class="block h-full rounded-full bg-bq-primary" style="width: {{ min(100, $unit['rate']) }}%"></span></span>
                                    <span class="bq-num font-semibold" title="{{ $unit['usedMinutes'] }} dari {{ $unit['offeredMinutes'] }} menit">{{ $percent($unit['rate']) }}</span>
                                </div>
                            @empty
                                <p class="text-bq-small text-bq-text-muted">Belum ada unit aktif.</p>
                            @endforelse
                        </x-ui.panel>
                    @endif
                </section>
                @break

            @case('layanan')
                @php $services = $report['services']; $maxRevenue = max([1, ...array_map(fn ($row) => abs($row['revenue']), $services)]); @endphp
                <x-ui.panel title="Pendapatan per layanan"
                    :description="'Jumlah '.$rupiah($summary['revenue']).' pendapatan bersih dan '.$number($summary['bookings']).' booking. Booking menurut tanggal sesi.'">
                    <h2 id="services-title" class="sr-only">Layanan menurut booking dan pendapatan</h2>
                    @if ($services === [])
                        <p class="text-bq-small text-bq-text-muted">Belum ada booking atau pembayaran pada periode ini.</p>
                    @else
                        <div class="grid gap-3">
                            @foreach ($services as $row)
                                <div class="grid grid-cols-[minmax(0,1.6fr)_minmax(0,2fr)_auto] items-center gap-3 text-bq-body">
                                    <span class="truncate" title="{{ $row['name'] }}">{{ $row['name'] }} <span class="text-bq-text-muted">· {{ $row['bookings'] }} booking</span></span>
                                    <span class="h-2 rounded-full bg-bq-surface-2"><span class="block h-full rounded-full bg-bq-primary" style="width: {{ round(max(0, $row['revenue']) / $maxRevenue * 100, 1) }}%"></span></span>
                                    <span class="bq-num font-semibold whitespace-nowrap">{{ $rupiah($row['revenue']) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-ui.panel>
                @break

            @case('pelanggan')
                @php $customers = $report['customers']; @endphp
                <section aria-labelledby="customers-title" class="space-y-4">
                    <h2 id="customers-title" class="sr-only">Pelanggan</h2>
                    @php
                        $more = [['value' => $number($customers['returning']), 'label' => 'pelanggan kembali', 'delta' => $delta($comparison['current']['returning'] ?? $customers['returning'], $comparison['returning'] ?? null)]];
                        if ($customers['advanceDays'] !== null) {
                            $more[] = ['value' => number_format($customers['advanceDays'], 1, ',', '.').' hari', 'label' => 'rata-rata booking sebelum sesi'];
                        }
                        if ($customers['promos'] !== null) {
                            $more[] = ['value' => array_sum(array_column($customers['promos'], 'uses')).' kali', 'label' => 'promo dipakai'];
                        }
                    @endphp
                    <x-ui.panel>
                        <x-ui.figures :value="$number($customers['new'])" label="pelanggan baru" :delta="$delta($comparison['current']['new'] ?? $customers['new'], $comparison['new'] ?? null)"
                            :context="$number($customers['active']).' pelanggan booking pada periode ini'" :more="$more" />
                    </x-ui.panel>
                    <section class="bq-panel" aria-labelledby="report-unpaid">
                        <div class="px-5 pt-4 pb-2">
                            <h3 id="report-unpaid" class="text-bq-section">Belum jadi membayar</h3>
                            <p class="text-bq-small text-bq-text-muted">Sudah memilih slot tetapi tidak membayar. Calon pelanggan paling dekat.</p>
                        </div>
                        <ul>
                            @forelse ($customers['unpaid'] as $booking)
                                <li class="flex items-center gap-3 border-t border-bq-border px-5 py-3">
                                    <x-ui.avatar :name="$booking['name']" class="shrink-0" />
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate font-semibold text-bq-ink" title="{{ $booking['name'] }}">{{ $booking['name'] }}</p>
                                        @php $details = $booking['service'].' · '.$booking['date'].' · Pembayaran tidak selesai'; @endphp
                                        <p class="truncate text-bq-small text-bq-text-muted" title="{{ $details }}">{{ $details }}</p>
                                    </div>
                                    @if ($booking['whatsAppUrl'])
                                        <a href="{{ $booking['whatsAppUrl'] }}" rel="noopener" target="_blank" class="bq-btn bq-btn-secondary bq-btn-sm shrink-0"
                                            aria-label="Buka WhatsApp untuk {{ $booking['name'] }}"><x-owner.icon name="whatsapp" class="h-4 w-4" />WhatsApp</a>
                                    @endif
                                </li>
                            @empty
                                <li class="border-t border-bq-border px-5 py-3 text-bq-small text-bq-text-muted">Tidak ada booking yang belum jadi membayar.</li>
                            @endforelse
                        </ul>
                    </section>
                    @if ($customers['promos'] !== null && $customers['promos'] !== [])
                        <x-ui.panel title="Pemakaian promo">
                            <ul class="divide-y divide-bq-border text-bq-body">
                                @foreach ($customers['promos'] as $promo)
                                    <li class="flex flex-wrap justify-between gap-2 py-2">
                                        <span>{{ $promo['customer'] }} · <span class="font-bq-display font-semibold">{{ $promo['code'] }}</span></span>
                                        <span class="text-bq-text-muted">{{ $promo['uses'] }} booking · potongan {{ $rupiah($promo['discount']) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </x-ui.panel>
                    @endif
                </section>
                @break

            @default
                <section aria-labelledby="summary-title" class="space-y-4">
                    <h2 id="summary-title" class="sr-only">Ringkasan</h2>
                    <x-ui.panel>
                        <div class="grid gap-6 sm:grid-cols-2">
                            <x-ui.figures :value="$rupiah($summary['revenue'])" label="pendapatan bersih" :delta="$delta($comparison['current']['net'] ?? $summary['revenue'], $comparison['net'] ?? null)" context="Menurut tanggal bayar" />
                            <x-ui.figures :value="$number($summary['bookings'])" label="booking" :delta="$delta($comparison['current']['bookings'] ?? $summary['bookings'], $comparison['bookings'] ?? null)" context="Menurut tanggal sesi" />
                        </div>
                    </x-ui.panel>
                    @php
                        $supporting = [
                            ['value' => $percent($summary['completionRate']), 'label' => 'selesai'],
                            ['value' => $percent($summary['cancellationRate']), 'label' => 'dibatalkan'],
                        ];
                        if ($summary['noShowRate'] !== null) {
                            $supporting[] = ['value' => $percent($summary['noShowRate']), 'label' => 'tidak datang'];
                        }
                        $supporting[] = ['value' => $summary['averageRating'] !== null ? number_format($summary['averageRating'], 1, ',', '.') : '—', 'label' => 'rating rata-rata'];
                        $supporting[] = ['value' => $number($summary['newCustomers']), 'label' => 'pelanggan baru'];
                        $supporting[] = ['value' => $summary['topService'] ?? '—', 'label' => 'layanan terlaris'];
                        if ($summary['showUnits']) {
                            $supporting[] = ['value' => $summary['topUnit'] ?? '—', 'label' => 'unit paling sibuk'];
                        }
                    @endphp
                    <x-ui.panel>
                        <div class="bq-figures-more">
                            @foreach ($supporting as $figure)
                                <div class="min-w-0 max-w-64"><b class="truncate" title="{{ $figure['value'] }}">{{ $figure['value'] }}</b><span>{{ $figure['label'] }}</span></div>
                            @endforeach
                        </div>
                    </x-ui.panel>
                    <p class="text-bq-small text-bq-text-muted">Booking dihitung menurut tanggal layanan; booking beberapa slot dihitung satu. Pendapatan dihitung dari pembayaran yang berhasil dikurangi refund.</p>
                </section>
        @endswitch
    @endif
</div>
@endsection
