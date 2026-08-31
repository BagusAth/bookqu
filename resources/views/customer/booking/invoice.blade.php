@extends('customer.layouts.app')

@section('title', 'Booking Berhasil')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden relative">
        
        <!-- Decorative Header Background -->
        <div class="h-32 bg-gradient-to-r from-primary-500 to-primary-600 absolute top-0 left-0 right-0 z-0"></div>
        
        <div class="relative z-10 p-8 flex flex-col items-center">
            @if ($payment->status === 'sukses')
                <!-- Success Icon -->
                <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center shadow-md mb-6">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                </div>
                
                <h1 class="text-3xl font-bold text-gray-900 text-center mb-2">Booking Berhasil!</h1>
                <p class="text-gray-600 text-center max-w-md">
                    Terima kasih, pembayaran Anda telah kami terima dan jadwal Anda sudah dikonfirmasi.
                </p>
            @elseif ($payment->status === 'pending')
                <!-- Pending Icon -->
                <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center shadow-md mb-6">
                    <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                
                <h1 class="text-3xl font-bold text-gray-900 text-center mb-2">Menunggu Pembayaran</h1>
                <p class="text-gray-600 text-center max-w-md">
                    Mohon selesaikan pembayaran Anda agar booking ini dapat dikonfirmasi.
                </p>
            @else
                <!-- Failed/Cancelled Icon -->
                <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center shadow-md mb-6">
                    <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                </div>
                
                <h1 class="text-3xl font-bold text-gray-900 text-center mb-2">Booking Dibatalkan</h1>
                <p class="text-gray-600 text-center max-w-md">
                    Maaf, pembayaran gagal atau waktu pembayaran telah habis.
                </p>
            @endif
        </div>

        <div class="px-8 pb-8">
            <!-- Ticket Info -->
            <div class="bg-gray-50 rounded-xl p-6 border border-gray-100 mb-8 relative">
                <!-- Ticket cutouts -->
                <div class="absolute w-6 h-6 bg-white rounded-full border border-gray-100 border-l-transparent border-t-transparent border-b-transparent -left-3 top-1/2 transform -translate-y-1/2"></div>
                <div class="absolute w-6 h-6 bg-white rounded-full border border-gray-100 border-r-transparent border-t-transparent border-b-transparent -right-3 top-1/2 transform -translate-y-1/2"></div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 border-b border-gray-200 border-dashed pb-6">
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Order ID</p>
                        <p class="font-mono font-bold text-gray-900">{{ $payment->order_id }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Status</p>
                        @if ($payment->status === 'sukses')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Lunas
                            </span>
                        @elseif ($payment->status === 'pending')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                Menunggu Pembayaran
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                Dibatalkan
                            </span>
                        @endif
                    </div>
                    
                    <div class="md:col-span-2 mt-2">
                        <p class="text-sm text-gray-500 mb-1">Layanan</p>
                        <p class="font-bold text-lg text-gray-900">{{ $booking->layanan->namalayanan }}</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-6 pt-6">
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Tanggal</p>
                        <p class="font-medium text-gray-900">{{ \Carbon\Carbon::parse($booking->tanggalbooking)->translatedFormat('d M Y') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Jam</p>
                        <p class="font-medium text-gray-900">{{ \Carbon\Carbon::parse($booking->jam)->format('H:i') }} WIB</p>
                    </div>
                </div>
            </div>

            <!-- Customer Details -->
            <h3 class="font-semibold text-gray-900 mb-4">Detail Pemesan</h3>
            <div class="bg-white border border-gray-100 rounded-lg p-5 grid grid-cols-1 md:grid-cols-2 gap-4 mb-8 text-sm">
                <div>
                    <p class="text-gray-500 mb-1">Nama</p>
                    <p class="font-medium text-gray-900">{{ $booking->namapelanggan }}</p>
                </div>
                <div>
                    <p class="text-gray-500 mb-1">No. HP / WhatsApp</p>
                    <p class="font-medium text-gray-900">{{ $booking->nomorhp }}</p>
                </div>
                <div class="md:col-span-2">
                    <p class="text-gray-500 mb-1">Email</p>
                    <p class="font-medium text-gray-900">{{ $booking->email }}</p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center mt-8">
                <button onclick="window.print()" class="flex-1 max-w-xs bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 font-medium py-3 px-6 rounded-lg shadow-sm transition-colors flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                    </svg>
                    Cetak Invoice
                </button>
                <a href="{{ route('customer.booking.program', $tenant->slug) }}" class="flex-1 max-w-xs bg-primary-600 hover:bg-primary-700 text-white font-medium py-3 px-6 rounded-lg shadow-sm transition-colors flex items-center justify-center gap-2">
                    Buat Pesanan Baru
                </a>
            </div>
        </div>
        
    </div>
</div>

<style>
    @media print {
        body {
            background-color: white;
        }
        nav, footer, .no-print {
            display: none !important;
        }
        .max-w-3xl {
            max-width: 100% !important;
        }
        .shadow-sm, .shadow-md {
            box-shadow: none !important;
        }
        .border, .border-gray-100 {
            border-color: #e5e7eb !important;
        }
        button {
            display: none !important;
        }
    }
</style>
@endsection
