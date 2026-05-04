<!-- 
  Pastikan project Laravel Anda sudah terinstall Tailwind CSS.
  Anda bisa langsung meletakkan kode ini di dalam file view .blade.php Anda.
-->

<section class="bg-white py-16 px-4 sm:px-6 lg:px-8 font-sans">
    <div class="max-w-6xl mx-auto">
        
        <!-- Header Section -->
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4 tracking-tight">
                Fitur Utama Untuk Bisnis Anda
            </h2>
            <p class="text-base md:text-lg text-gray-500 max-w-2xl mx-auto leading-relaxed">
                Dirancang untuk kecepatan dan kemudahan penggunaan, BookQu memberikan kontrol penuh atas operasional harian Anda.
            </p>
        </div>

        <!-- Grid Container -->
        <!-- Menggunakan 1 kolom di HP, 2 kolom di Tablet, dan 3 kolom di Desktop -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            
            <!-- Card 1: Booking Management (Lebar 2 kolom di desktop/tablet) -->
            <div class="col-span-1 md:col-span-2 lg:col-span-2 bg-white border border-gray-100 shadow-sm rounded-[24px] overflow-hidden flex flex-col justify-between group hover:shadow-md transition-shadow duration-300">
                <div class="p-8 pb-0">
                    <!-- Icon -->
                    <div class="w-12 h-12 mb-6 text-indigo-600">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z" />
                        </svg>
                    </div>
                    <h3 class="text-xl md:text-2xl font-bold text-gray-900 mb-3">Booking Management</h3>
                    <p class="text-gray-500 text-sm md:text-base leading-relaxed max-w-md">
                        Terima pesanan 24/7 secara otomatis. Biarkan pelanggan memilih jadwal tanpa perlu chat manual satu per satu.
                    </p>
                </div>
                <!-- Image Container -->
                <div class="mt-8 w-full h-48 md:h-64 bg-gray-100">
                    <img src="https://images.unsplash.com/photo-1544396821-4dd40b938ad3?q=80&w=1000&auto=format&fit=crop" alt="Booking Management Dashboard" class="w-full h-full object-cover object-center" />
                </div>
            </div>

            <!-- Card 2: Real-time Schedule (Lebar 1 kolom) -->
            <div class="col-span-1 bg-[#F8F7FF] rounded-[24px] p-8 flex flex-col hover:shadow-md transition-shadow duration-300">
                <!-- Icon -->
                <div class="w-12 h-12 mb-6 text-indigo-600">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                    </svg>
                </div>
                <h3 class="text-xl md:text-2xl font-bold text-gray-900 mb-3">Real-time Schedule</h3>
                <p class="text-gray-500 text-sm md:text-base leading-relaxed">
                    Sinkronisasi instan di semua perangkat. Hindari double-booking dengan kalender pintar kami.
                </p>
            </div>

            <!-- Card 3: Payment Integration (Lebar 1 kolom) -->
            <div class="col-span-1 bg-[#F8F7FF] rounded-[24px] p-8 flex flex-col hover:shadow-md transition-shadow duration-300">
                <!-- Icon -->
                <div class="w-12 h-12 mb-6 text-indigo-600">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                </div>
                <h3 class="text-xl md:text-2xl font-bold text-gray-900 mb-3">Payment Integration</h3>
                <p class="text-gray-500 text-sm md:text-base leading-relaxed">
                    Terima pembayaran DP atau pelunasan langsung lewat sistem dengan berbagai metode pembayaran lokal.
                </p>
            </div>

            <!-- Card 4: Analytics Dashboard (Lebar 2 kolom di desktop, 1 kolom di tablet/HP) -->
            <div class="col-span-1 md:col-span-1 lg:col-span-2 bg-white border border-gray-100 shadow-sm rounded-[24px] p-8 flex flex-col md:flex-row items-center gap-8 group hover:shadow-md transition-shadow duration-300">
                
                <!-- Text Content -->
                <div class="flex-1 w-full">
                    <!-- Icon -->
                    <div class="w-12 h-12 mb-6 text-indigo-600">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                        </svg>
                    </div>
                    <h3 class="text-xl md:text-2xl font-bold text-gray-900 mb-3">Analytics Dashboard</h3>
                    <p class="text-gray-500 text-sm md:text-base leading-relaxed">
                        Pantau performa bisnis, pendapatan harian, hingga staf paling produktif melalui data visual yang intuitif.
                    </p>
                </div>
                
                <!-- Image Container -->
                <div class="flex-1 w-full mt-6 md:mt-0">
                    <div class="rounded-xl overflow-hidden shadow-lg border border-gray-800">
                        <img src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?q=80&w=1000&auto=format&fit=crop" alt="Analytics Dashboard Chart" class="w-full h-auto object-cover" />
                    </div>
                </div>

            </div>
        </div>

    </div>
</section>