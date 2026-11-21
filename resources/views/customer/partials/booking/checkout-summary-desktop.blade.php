{{-- Price lines of the checkout (design A §7.3), used inside a <dl>. Amounts come from the server quote. --}}
<div class="flex justify-between gap-4">
    <dt class="text-sf-muted">Tanggal</dt>
    <dd class="text-right font-bold">{{ $checkoutSummary['date'] }}</dd>
</div>
<div class="flex justify-between gap-4">
    <dt class="text-sf-muted">Sesi</dt>
    <dd class="sf-num text-right font-bold">{{ count($checkoutSummary['sessions']) }} sesi ({{ $selectedMinutes }} menit)</dd>
</div>
@foreach ($checkoutSummary['sessions'] as $session)
    <div class="sf-num flex justify-between gap-4 text-[13px] text-sf-muted">
        <dt>{{ $session['time'] }} WIB</dt>
        <dd class="whitespace-nowrap">{{ $session['price'] }}</dd>
    </div>
@endforeach
<div class="flex justify-between gap-4 border-t border-sf-divider pt-2.5">
    <dt class="text-sf-muted">Harga sesi</dt>
    <dd class="sf-num whitespace-nowrap" x-text="ready ? (display?.base || initialTotal) : '—'">{{ $checkoutSummary['total'] }}</dd>
</div>
<template x-for="item in ready ? (display?.addOns || []) : []" :key="item.id">
    <div class="flex justify-between gap-4">
        <dt class="text-sf-muted" x-text="item.name + ' × ' + item.quantity"></dt>
        <dd class="sf-num whitespace-nowrap" x-text="item.amount"></dd>
    </div>
</template>
<div class="flex justify-between gap-4" x-show="ready && display?.promo" x-cloak>
    <dt class="text-sf-muted">Diskon promo</dt>
    <dd class="sf-num whitespace-nowrap text-sf-success" x-text="display?.promo"></dd>
</div>
