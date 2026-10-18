<footer id="about" class="bg-slate-100">
    <div class="mx-auto max-w-6xl px-6 py-12 lg:px-8">
        <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-4">
            <div class="col-span-1 md:col-span-2">
                <a href="/" class="flex items-center mb-4">
                    <img src="{{ asset('images/logo.png') }}" alt="BookQu Logo" class="h-8 w-auto" />
                </a>
                <p class="mt-4 text-sm text-slate-600 max-w-xs">
                    Platform manajemen booking terintegrasi untuk membantu digitalisasi bisnis jasa Anda.
                </p>
            </div>
            <div>
                <h4 class="text-sm font-semibold text-slate-700">Produk</h4>
                <ul class="mt-3 space-y-2 text-sm text-slate-600">
                    <li><a href="{{ url('/#features') }}" class="hover:text-slate-900 transition-colors">Fitur Utama</a></li>
                    <li><a href="{{ url('/#pricing') }}" class="hover:text-slate-900 transition-colors">Integrasi Pembayaran</a></li>
                    <li><a href="{{ url('/#features') }}" class="hover:text-slate-900 transition-colors">Mobile Responsive</a></li>
                    <li><a href="{{ url('/#features') }}" class="hover:text-slate-900 transition-colors">Manajemen Jadwal</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-sm font-semibold text-slate-700">Solusi</h4>
                <ul class="mt-3 space-y-2 text-sm text-slate-600">
                    <li><a href="{{ url('/#features') }}" class="hover:text-slate-900 transition-colors">Salon &amp; Spa</a></li>
                    <li><a href="{{ url('/#features') }}" class="hover:text-slate-900 transition-colors">Klinik Kesehatan</a></li>
                    <li><a href="{{ url('/#features') }}" class="hover:text-slate-900 transition-colors">Studio Foto</a></li>
                    <li><a href="{{ url('/#features') }}" class="hover:text-slate-900 transition-colors">Konsultan Jasa</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-sm font-semibold text-slate-700">Kontak</h4>
                <ul class="mt-3 space-y-2 text-sm text-slate-600">
                    <li><a href="mailto:bookquind@gmail.com" class="hover:text-slate-900 transition-colors">bookquind@gmail.com</a></li>
                    <li><a href="https://wa.me/62895416023108" target="_blank" rel="noopener noreferrer" class="hover:text-slate-900 transition-colors">+62 895416023108</a></li>
                    <li>Tembalang, Semarang, Indonesia</li>
                </ul>
            </div>
        </div>
        <div class="mt-10 flex flex-col items-center justify-between gap-4 border-t border-slate-200 pt-6 text-xs text-slate-600 md:flex-row">
            <span>Copyright &copy; {{ date('Y') }} BookQu. Hak Cipta Dilindungi Undang-Undang.</span>
            <div class="flex items-center gap-4">
                <a href="{{ route('privacy-policy') }}" class="text-slate-600 hover:text-[#4F46E5] transition-colors">Kebijakan Privasi</a>
                <a href="{{ route('terms-conditions') }}" class="text-slate-600 hover:text-[#4F46E5] transition-colors">Syarat & Ketentuan</a>
            </div>
        </div>
    </div>
</footer>
