{{--
    Customer payments of the Pembayaran page (BQ-141, owner-portal.md §8.12 Tab Transaksi): order id,
    time, customer, service, amount, method, status and refund amount. Rows come prepared from
    App\Actions\Owner\GetPaymentsPage.
--}}
@if ($rows->total() === 0)
    <p class="px-4 py-6 text-sm text-bq-text-muted">{{ $emptyText }}</p>
@else
    <x-ui.table :columns="['Waktu', 'Pelanggan', 'Layanan', 'Metode', 'Dibayar']" label="Transaksi pembayaran">
        @foreach ($rows as $row)
            <div role="row" class="bq-table-row bq-table-double">
                <div role="cell" class="bq-table-cell min-w-0">
                    <span class="block truncate bq-num" title="{{ $row['time'] }}">{{ $row['time'] }}</span>
                    <span class="sr-only">{{ $row['orderId'] }}</span>
                </div>
                <div role="cell" class="bq-table-cell bq-table-main min-w-0">
                    <span class="block truncate font-semibold" title="{{ $row['customer'] }}">{{ $row['customer'] }}</span>
                    <div class="bq-table-caption text-bq-small text-bq-text-muted">
                        <span class="md:hidden">{{ $row['status']['label'] }} · </span>
                        @include('owner.partials.payments.booking-link')
                        @unless ($row['bookingPanel']){{ $row['orderId'] }}@endunless
                    </div>
                </div>
                <div role="cell" class="bq-table-cell truncate text-bq-small" title="{{ $row['service'] }}">{{ $row['service'] }}</div>
                <div role="cell" class="bq-table-cell min-w-0">
                    <span class="block truncate text-bq-small" title="{{ $row['method'] }}">{{ $row['method'] }}</span>
                    <x-ui.status-dot :tone="$row['status']['tone']">{{ $row['status']['label'] }}</x-ui.status-dot>
                </div>
                <div role="cell" class="bq-table-cell bq-table-end bq-num">
                    <span class="block">{{ $row['amount'] }}</span>
                    @if ($row['refund'])<span class="block text-bq-small text-bq-danger">{{ $row['refund'] }}</span>@endif
                </div>
            </div>
        @endforeach
    </x-ui.table>
@endif
