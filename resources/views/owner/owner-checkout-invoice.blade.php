@extends('layouts.owner-layout')

@section('title', 'Checkout — Invoice')

@section('content')
<div class="mx-auto max-w-5xl space-y-6">

    {{-- Stepper --}}
    <div class="flex items-center justify-center gap-0 py-2">
        <div class="flex items-center gap-2">
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-bq-primary text-sm font-bold text-white shadow-md shadow-bq-primary/30">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </div>
            <span class="hidden text-sm font-semibold text-bq-primary sm:inline">Review</span>
        </div>
        <div class="mx-3 h-0.5 w-8 rounded bg-bq-primary sm:w-16"></div>
        <div class="flex items-center gap-2">
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-bq-primary text-sm font-bold text-white shadow-md shadow-bq-primary/30">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </div>
            <span class="hidden text-sm font-semibold text-bq-primary sm:inline">Pembayaran</span>
        </div>
        <div class="mx-3 h-0.5 w-8 rounded bg-bq-primary sm:w-16"></div>
        <div class="flex items-center gap-2">
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-500 text-sm font-bold text-white shadow-md shadow-emerald-500/30">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </div>
            <span class="hidden text-sm font-semibold text-emerald-600 sm:inline">Selesai</span>
        </div>
    </div>

    {{-- Success Banner --}}
    <div class="rounded-2xl border border-emerald-200 bg-gradient-to-br from-emerald-50 via-green-50 to-teal-50 p-6 text-center">
        {{-- Animated Checkmark --}}
        <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-emerald-100 shadow-lg shadow-emerald-200/50">
            <div class="invoice-checkmark">
                <svg class="h-10 w-10 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" class="checkmark-path"/>
                </svg>
            </div>
        </div>
        <h1 class="text-2xl font-bold text-emerald-800">Pembayaran Berhasil! 🎉</h1>
        <p class="mt-2 text-sm text-emerald-700">Terima kasih! Subscription Anda telah aktif.</p>
        <p class="mt-1 text-xs text-emerald-600/70">Konfirmasi pembayaran dan invoice telah dikirim ke email Anda.</p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">

        {{-- Left Column: Invoice Detail --}}
        <div class="lg:col-span-3 space-y-5">

            {{-- Invoice Card --}}
            <div class="rounded-xl border border-bq-border bg-bq-surface overflow-hidden" id="invoice-card">
                {{-- Invoice Header --}}
                <div class="bg-gradient-to-r from-bq-primary to-violet-600 px-6 py-5 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-white/70 uppercase tracking-wider">Invoice</p>
                            <p class="text-lg font-bold">BookQu</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-medium text-white/70 uppercase tracking-wider">Kode Booking</p>
                            <p class="text-lg font-bold font-mono">{{ $payment->order_id }}</p>
                        </div>
                    </div>
                </div>

                {{-- Invoice Body --}}
                <div class="p-6 space-y-5">

                    {{-- Billing Info --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs font-medium text-bq-text-muted uppercase tracking-wider mb-1">Ditagihkan Kepada</p>
                            <p class="text-sm font-semibold text-bq-text">{{ $payment->nama_pembayar }}</p>
                            <p class="text-xs text-bq-text-muted">{{ $payment->email_pembayar }}</p>
                            <p class="text-xs text-bq-text-muted">{{ $payment->hp_pembayar }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-medium text-bq-text-muted uppercase tracking-wider mb-1">Tanggal Pembayaran</p>
                            <p class="text-sm font-semibold text-bq-text">{{ $payment->updated_at->format('d M Y') }}</p>
                            <p class="text-xs text-bq-text-muted">{{ $payment->updated_at->format('H:i') }} WIB</p>
                        </div>
                    </div>

                    {{-- Status Badge --}}
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3"/></svg>
                            LUNAS
                        </span>
                        <span class="text-xs text-bq-text-muted capitalize">via {{ str_replace('_', ' ', $payment->metode) }}</span>
                    </div>

                    {{-- Item Table --}}
                    <div class="rounded-lg border border-bq-border overflow-hidden">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-bq-background">
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-bq-text-muted uppercase tracking-wider">Item</th>
                                    <th class="px-4 py-2.5 text-right text-xs font-semibold text-bq-text-muted uppercase tracking-wider">Harga</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-bq-border">
                                <tr>
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-bq-text">Subscription Plan {{ ucfirst($plan->namapaket) }}</p>
                                        <p class="text-xs text-bq-text-muted">Durasi 1 bulan</p>
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium text-bq-text">Rp {{ number_format($plan->hargabulanan, 0, ',', '.') }}</td>
                                </tr>
                                @if($payment->jumlah > $plan->hargabulanan)
                                    <tr>
                                        <td class="px-4 py-3 text-bq-text-muted">Biaya Layanan Platform</td>
                                        <td class="px-4 py-3 text-right text-bq-text-muted">Rp {{ number_format($payment->jumlah - $plan->hargabulanan, 0, ',', '.') }}</td>
                                    </tr>
                                @endif
                            </tbody>
                            <tfoot>
                                <tr class="bg-bq-background">
                                    <td class="px-4 py-3 font-bold text-bq-text">Total Pembayaran</td>
                                    <td class="px-4 py-3 text-right text-lg font-bold text-bq-primary">Rp {{ number_format($payment->jumlah, 0, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    {{-- Subscription Period --}}
                    @if($subscription)
                        <div class="rounded-lg border border-bq-primary/20 bg-bq-primary/5 p-4">
                            <p class="text-xs font-semibold text-bq-primary uppercase tracking-wider mb-2">Periode Langganan</p>
                            <div class="flex items-center gap-3">
                                <div class="text-center">
                                    <p class="text-xs text-bq-text-muted">Mulai</p>
                                    <p class="text-sm font-bold text-bq-text">{{ $subscription->langganan_mulai->format('d M Y') }}</p>
                                </div>
                                <div class="flex-1 border-t border-dashed border-bq-primary/30"></div>
                                <div class="text-center">
                                    <p class="text-xs text-bq-text-muted">Berakhir</p>
                                    <p class="text-sm font-bold text-bq-text">{{ $subscription->langganan_berakhir->format('d M Y') }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Notes --}}
                    @if($payment->catatan)
                        <div class="rounded-lg bg-amber-50 border border-amber-200 p-3">
                            <p class="text-xs font-semibold text-amber-700 mb-1">Catatan:</p>
                            <p class="text-xs text-amber-700">{{ $payment->catatan }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Right Column: Actions --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- Quick Actions --}}
            <div class="rounded-xl border border-bq-border bg-bq-surface p-5 space-y-3">
                <h2 class="text-sm font-semibold text-bq-text border-b border-bq-border pb-3">Aksi Cepat</h2>

                {{-- Save as Image --}}
                <button type="button" id="btn-save-image" onclick="saveInvoiceAsImage()"
                    class="w-full rounded-lg border border-bq-border bg-bq-background py-3 text-sm font-semibold text-bq-text transition-all hover:bg-bq-surface hover:border-bq-primary hover:text-bq-primary flex items-center justify-center gap-2">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Simpan Gambar Invoice
                </button>

                {{-- Back to Dashboard --}}
                <a href="{{ route('owner.dashboard') }}" id="btn-back-dashboard"
                    class="w-full rounded-lg bg-bq-primary py-3 text-sm font-bold text-white shadow-md shadow-bq-primary/25 transition-all hover:bg-bq-primary-hover hover:shadow-lg hover:-translate-y-0.5 flex items-center justify-center gap-2">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Kembali ke Dashboard
                </a>

                {{-- Back to Subscription --}}
                <a href="{{ route('owner.subscription') }}" id="btn-back-subscription"
                    class="w-full rounded-lg border border-bq-border bg-bq-background py-3 text-sm font-semibold text-bq-text transition-all hover:bg-bq-surface flex items-center justify-center gap-2">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    Lihat Subscription
                </a>
            </div>

            {{-- Arrival Info --}}
            <div class="rounded-xl border border-bq-primary/20 bg-gradient-to-br from-bq-primary/5 to-violet-500/5 p-5">
                <div class="flex items-start gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-bq-primary/10 shrink-0">
                        <svg class="h-5 w-5 text-bq-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-bq-text">Informasi Penting</p>
                        <ul class="mt-2 space-y-1.5 text-xs text-bq-text-muted">
                            <li>• Subscription Anda langsung aktif setelah pembayaran diverifikasi.</li>
                            <li>• Simpan kode booking <strong class="text-bq-primary font-mono">{{ $payment->order_id }}</strong> sebagai referensi.</li>
                            <li>• Invoice juga dikirim ke <strong>{{ $payment->email_pembayar }}</strong>.</li>
                            <li>• Hubungi support jika ada kendala.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- html2canvas CDN --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<script>
async function saveInvoiceAsImage() {
    const btn = document.getElementById('btn-save-image');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Menyimpan...';
    btn.disabled = true;

    try {
        const invoiceEl = document.getElementById('invoice-card');
        const canvas = await html2canvas(invoiceEl, {
            scale: 2,
            useCORS: true,
            backgroundColor: '#ffffff',
            logging: false,
        });

        const link = document.createElement('a');
        link.download = 'invoice-{{ $payment->order_id }}.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
    } catch (e) {
        console.error('Failed to save invoice as image:', e);
        alert('Gagal menyimpan gambar. Silakan coba screenshot secara manual.');
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}
</script>

<style>
/* Checkmark animation */
.checkmark-path {
    stroke-dasharray: 30;
    stroke-dashoffset: 30;
    animation: checkmark-draw 0.6s ease-out 0.3s forwards;
}

@keyframes checkmark-draw {
    to {
        stroke-dashoffset: 0;
    }
}

.invoice-checkmark {
    animation: checkmark-pop 0.4s ease-out 0.1s both;
}

@keyframes checkmark-pop {
    0% { transform: scale(0); opacity: 0; }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); opacity: 1; }
}
</style>
@endsection
