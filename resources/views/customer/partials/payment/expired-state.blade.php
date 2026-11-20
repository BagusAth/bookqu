<section id="state-expired-card" class="{{ $currentState === 'expired' ? 'block' : 'hidden' }}">
    <x-ui.panel class="border-bq-warning-border">
        <x-customer.page-header title="Pembayaran kedaluwarsa" description="Waktu pembayaran sudah habis. Pilih jadwal lagi untuk memesan kembali." />
        <x-ui.kv class="mt-4" :items="['Nomor pesanan' => $payment->order_id]" />
        <div class="fixed inset-x-0 bottom-0 z-30 border-t border-bq-border bg-bq-surface p-4 sm:static sm:mt-4 sm:border-0 sm:p-0">
            <x-ui.button :href="$storefrontUrl ?? \App\Support\CustomerBookingRoutes::url('customer.booking.program', $tenant->slug)"
                variant="primary" class="min-h-11 w-full sm:w-auto">Pilih jadwal lagi</x-ui.button>
        </div>
    </x-ui.panel>
</section>
