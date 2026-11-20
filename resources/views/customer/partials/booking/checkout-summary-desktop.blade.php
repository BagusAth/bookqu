<x-customer.summary :items="['Layanan' => $service->namalayanan, 'Tanggal' => $checkoutSummary['date']]">
    <p class="mb-2 mt-3 text-bq-small text-bq-text-muted">{{ count($checkoutSummary['sessions']) }} sesi ({{ $selectedMinutes }} menit)</p>
    <ul class="space-y-2">
        @foreach ($checkoutSummary['sessions'] as $session)
            <li class="bq-num flex flex-wrap justify-between gap-2 text-bq-small">
                <span>{{ $session['time'] }} WIB</span>
                <span>{{ $session['price'] }}</span>
            </li>
        @endforeach
    </ul>
    <dl class="mt-3 space-y-2 border-t border-bq-border pt-3 text-bq-body">
        <div class="flex justify-between gap-2">
            <dt>Harga sesi</dt>
            <dd class="bq-num whitespace-nowrap" x-text="ready ? (display?.base || initialTotal) : '—'">{{ $checkoutSummary['total'] }}</dd>
        </div>
        <template x-for="item in ready ? (display?.addOns || []) : []" :key="item.id">
            <div class="flex justify-between gap-2">
                <dt x-text="item.name + ' × ' + item.quantity"></dt>
                <dd class="bq-num whitespace-nowrap" x-text="item.amount"></dd>
            </div>
        </template>
        <div class="flex justify-between gap-2" x-show="ready && display?.promo" x-cloak>
            <dt>Diskon promo</dt>
            <dd class="bq-num whitespace-nowrap" x-text="display?.promo"></dd>
        </div>
        <div class="flex justify-between gap-2 border-t border-bq-border pt-3 font-semibold">
            <dt>Total</dt>
            <dd class="bq-num whitespace-nowrap" x-text="totalLabel">{{ $checkoutSummary['total'] }}</dd>
        </div>
    </dl>
</x-customer.summary>
