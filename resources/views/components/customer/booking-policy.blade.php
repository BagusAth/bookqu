@props(['policy'])

<div class="booking-policy rounded-2xl border border-bq-border bg-bq-background p-4 sm:p-5">
    <h4 class="text-sm font-semibold">Kebijakan booking</h4>
    <ul class="mt-2 space-y-1 text-sm text-bq-text-muted">
        <li>Pindah jadwal paling lambat {{ $policy['reschedule_before_hours'] ?? 24 }} jam sebelum sesi.</li>
        @if ($policy['refunds_enabled'] ?? false)
            <li>Ajukan refund paling lambat {{ $policy['cancel_before_hours'] ?? 24 }} jam sebelum sesi. Pemilik meninjau pengajuan Anda.</li>
        @else
            <li>Hubungi pemilik untuk bantuan pembatalan atau refund.</li>
        @endif
    </ul>
</div>
