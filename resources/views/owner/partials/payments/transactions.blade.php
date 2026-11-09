{{--
    Customer payments of the Pembayaran page (BQ-141, owner-portal.md §8.12 Tab Transaksi): order id,
    time, customer, service, amount, method, status and refund amount. Rows come prepared from
    App\Actions\Owner\GetPaymentsPage.
--}}
@if ($rows->total() === 0)
    <p class="px-4 py-6 text-sm text-bq-text-muted">{{ $emptyText }}</p>
@else
    <div class="hidden overflow-x-auto md:block">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-bq-border bg-bq-background text-xs text-bq-text-muted">
                <tr>
                    <th scope="col" class="px-4 py-2.5 font-medium">Order</th>
                    <th scope="col" class="px-4 py-2.5 font-medium">Pelanggan</th>
                    <th scope="col" class="px-4 py-2.5 font-medium">Layanan</th>
                    <th scope="col" class="px-4 py-2.5 text-right font-medium">Jumlah</th>
                    <th scope="col" class="px-4 py-2.5 font-medium">Metode</th>
                    <th scope="col" class="px-4 py-2.5 font-medium">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-bq-border">
                @foreach ($rows as $row)
                    <tr>
                        <td class="px-4 py-3">
                            <span class="block text-bq-text">{{ $row['orderId'] }}</span>
                            <span class="block text-xs tabular-nums text-bq-text-muted">{{ $row['time'] }}</span>
                        </td>
                        <td class="px-4 py-3 text-bq-text">
                            {{ $row['customer'] }}
                            @if ($row['bookingCode'] !== '')<span class="block text-xs text-bq-text-muted">{{ $row['bookingCode'] }}</span>@endif
                        </td>
                        <td class="px-4 py-3 text-bq-text-muted">{{ $row['service'] }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums text-bq-text">
                            {{ $row['amount'] }}
                            @if ($row['refund'])<span class="block text-xs text-bq-danger">{{ $row['refund'] }}</span>@endif
                        </td>
                        <td class="px-4 py-3 text-bq-text-muted">{{ $row['method'] }}</td>
                        <td class="px-4 py-3"><span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $toneClass($row['status']['tone']) }}">{{ $row['status']['label'] }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <ul class="divide-y divide-bq-border md:hidden">
        @foreach ($rows as $row)
            <li class="space-y-1 px-4 py-3 text-sm">
                <div class="flex items-center justify-between gap-2">
                    <span class="truncate text-bq-text">{{ $row['customer'] }}</span>
                    <span class="inline-flex shrink-0 items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $toneClass($row['status']['tone']) }}">{{ $row['status']['label'] }}</span>
                </div>
                <p class="text-xs text-bq-text-muted">{{ $row['orderId'] }} · {{ $row['time'] }}</p>
                <p class="text-xs text-bq-text-muted">{{ $row['service'] }} · {{ $row['method'] }}</p>
                <p class="tabular-nums text-bq-text">{{ $row['amount'] }}@if ($row['refund']) <span class="text-xs text-bq-danger">· {{ $row['refund'] }}</span>@endif</p>
            </li>
        @endforeach
    </ul>
@endif
