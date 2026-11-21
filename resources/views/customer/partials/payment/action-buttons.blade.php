<button type="button" class="min-h-11 px-1 text-sm font-bold text-sf-error"
    x-data x-on:click="$dispatch('open-confirm', { title: 'Batalkan pesanan?', message: 'Jadwal yang ditahan akan dilepas. Pilih jadwal lagi untuk memesan kembali.', formId: 'customer-cancel-payment', confirmText: 'Batalkan pesanan', cancelText: 'Kembali', trigger: $el })">
    Batalkan pesanan
</button>
