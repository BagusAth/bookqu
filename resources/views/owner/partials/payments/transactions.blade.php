{{--
    Customer payments of the Pembayaran page (BQ-141, owner-portal.md §8.12 Tab Transaksi). BQ-162 (prototype K6, K7):
    one line per payment; the status shows only when it is not a success, beside the amount; the order code stays in the booking panel.
--}}
@if ($rows->total() === 0)
    <p class="px-5 py-6 text-bq-small text-bq-text-muted">{{ $emptyText }}</p>
@else
    <x-ui.table class="rounded-none border-0" :columns="['Waktu', 'Pelanggan', 'Layanan', 'Metode', 'Jumlah']" widths="auto minmax(160px, 1fr) minmax(0, 1.5fr) auto auto" label="Transaksi pembayaran">
        @foreach ($rows as $row)
            @php $success = $row['status']['tone'] === 'success'; @endphp
            <div role="row" @class(['bq-table-row', 'text-bq-text-muted' => $row['status']['tone'] === 'danger'])>
                <div role="cell" class="bq-table-cell whitespace-nowrap">
                    <span class="bq-num">{{ $row['time'] }}</span>
                    <span class="sr-only">{{ $row['orderId'] }}</span>
                </div>
                <div role="cell" class="bq-table-cell bq-table-main min-w-0">
                    <span class="block truncate font-semibold text-bq-ink" title="{{ $row['customer'] }}">{{ $row['customer'] }}</span>
                    <span class="bq-table-caption block truncate text-bq-small text-bq-text-muted md:hidden">{{ $success ? '' : $row['status']['label'].' · ' }}{{ $row['time'] }} · {{ $row['method'] }}</span>
                </div>
                <div role="cell" class="bq-table-cell min-w-0 truncate" title="{{ $row['service'] }}">
                    @if ($row['bookingPanel'])
                        <a href="{{ $row['bookingHref'] }}" x-data class="text-bq-ink hover:text-bq-primary" title="{{ $row['bookingCode'] }} · {{ $row['service'] }}"
                            @click.prevent="$dispatch('open-booking-panel', { url: @js($row['bookingPanel']), trigger: $el })">{{ $row['service'] }}</a>
                    @else
                        {{ $row['service'] }}
                    @endif
                </div>
                <div role="cell" class="bq-table-cell whitespace-nowrap">{{ $row['method'] }}</div>
                <div role="cell" class="bq-table-cell bq-table-end">
                    <span class="flex items-center justify-end gap-2 whitespace-nowrap">
                        @unless ($success)<x-ui.badge :tone="$row['status']['tone'] === 'danger' ? 'danger' : ($row['status']['tone'] === 'warning' ? 'warning' : 'neutral')">{{ $row['status']['label'] }}</x-ui.badge>@endunless
                        <span class="bq-num">{{ $row['amount'] }}</span>
                    </span>
                    @if ($row['refund'])<span class="block text-right text-bq-small text-bq-danger">{{ $row['refund'] }}</span>@endif
                </div>
            </div>
        @endforeach
    </x-ui.table>
@endif
