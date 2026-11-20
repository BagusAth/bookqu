<section id="state-failed-card" class="{{ $currentState === 'failed' ? 'block' : 'hidden' }}">
    <x-ui.panel class="border-bq-danger-border">
        <x-customer.page-header title="Pembayaran gagal" description="Pilih kembali jadwal Anda untuk melakukan pemesanan baru." />
        <x-ui.kv class="mt-4" :items="['Nomor pesanan' => $payment->order_id, 'Total' => $paymentTotal]" />
        <div class="fixed inset-x-0 bottom-0 z-30 border-t border-bq-border bg-bq-surface p-4 sm:static sm:mt-4 sm:border-0 sm:p-0">
            <x-ui.button :href="$storefrontUrl ?? \App\Support\CustomerBookingRoutes::url('customer.booking.program', $tenant->slug)"
                variant="primary" class="min-h-11 w-full sm:w-auto">Pilih jadwal lagi</x-ui.button>
        </div>
    </x-ui.panel>
</section>
