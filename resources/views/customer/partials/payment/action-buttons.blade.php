<div class="flex flex-wrap gap-2">
    <x-ui.button id="check-status-btn" class="min-h-11">Periksa status pembayaran</x-ui.button>
    <x-ui.button variant="danger" class="min-h-11"
        x-on:click="$dispatch('open-confirm', { title: 'Batalkan pembayaran?', message: 'Anda akan kembali ke daftar layanan. Pilih jadwal lagi untuk melanjutkan.', formId: 'customer-cancel-payment', confirmText: 'Batalkan pembayaran', cancelText: 'Kembali', trigger: $el })">
        Batalkan pembayaran
    </x-ui.button>
</div>
