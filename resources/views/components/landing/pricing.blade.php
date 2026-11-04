<section id="pricing" class="py-24 px-4 sm:px-6 lg:px-8 bg-slate-50" x-data="{ activeMobileTab: 1 }">
    <div class="max-w-7xl mx-auto">
        <!-- Header Section -->
        <div class="text-center mb-12 sm:mb-16 animate-fade-in">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4 tracking-tight">
                Pilih Paket Sesuai Kebutuhan
            </h2>
            <p class="text-base md:text-lg text-gray-500 max-w-2xl mx-auto">
                Transparan tanpa biaya tersembunyi. Tingkatkan paket Anda seiring berkembangnya bisnis.
            </p>
        </div>

        <!-- Mobile Segmented Tab Switcher (< 1024px) -->
        <div class="block lg:hidden max-w-sm mx-auto mb-8 px-2">
            <div class="bg-slate-200/90 p-1.5 rounded-2xl flex items-center justify-between gap-1 shadow-inner border border-slate-300/50">
                <button 
                    type="button" 
                    @click="activeMobileTab = 0; $dispatch('set-active-pricing', 0)"
                    :class="activeMobileTab === 0 ? 'bg-white text-slate-900 shadow-sm font-bold' : 'text-slate-600 font-medium hover:text-slate-900'"
                    class="flex-1 py-2.5 px-3 text-xs sm:text-sm rounded-xl transition-all duration-200 text-center cursor-pointer">
                    Small
                </button>
                <button 
                    type="button" 
                    @click="activeMobileTab = 1; $dispatch('set-active-pricing', 1)"
                    :class="activeMobileTab === 1 ? 'bg-white text-[#4F46E5] shadow-sm font-bold' : 'text-slate-600 font-medium hover:text-slate-900'"
                    class="flex-1 py-2.5 px-3 text-xs sm:text-sm rounded-xl transition-all duration-200 text-center cursor-pointer flex items-center justify-center gap-1">
                    <span>Medium</span>
                    <span class="text-amber-500 text-xs">★</span>
                </button>
                <button 
                    type="button" 
                    @click="activeMobileTab = 2; $dispatch('set-active-pricing', 2)"
                    :class="activeMobileTab === 2 ? 'bg-white text-purple-700 shadow-sm font-bold' : 'text-slate-600 font-medium hover:text-slate-900'"
                    class="flex-1 py-2.5 px-3 text-xs sm:text-sm rounded-xl transition-all duration-200 text-center cursor-pointer">
                    Pro
                </button>
            </div>
        </div>

        <!-- Pricing Cards Container -->
        <div class="pricing-container grid grid-cols-1 lg:grid-cols-3 gap-8 max-w-6xl mx-auto mt-6 lg:mt-12 items-stretch" id="pricing-grid">
            <!-- CARD 1: Small -->
            <div 
                class="pricing-card rounded-[2rem] p-7 sm:p-9 flex-col justify-between" 
                :class="activeMobileTab === 0 ? 'flex' : 'hidden lg:flex'" 
                data-index="0">
                <div>
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="text-lg font-bold text-gray-900 tracking-tight">Small</h3>
                        <span class="bg-slate-100 text-slate-600 text-xs font-semibold px-3 py-1 rounded-full uppercase tracking-wide">
                            Starter
                        </span>
                    </div>

                    <div class="flex items-baseline mb-4">
                        <span class="text-3xl sm:text-4xl font-extrabold text-gray-900 tracking-tight">Rp{{ number_format($plans->get('small')?->hargabulanan ?? 149000, 0, ',', '.') }}</span>
                        <span class="text-gray-500 font-medium ml-1.5 text-sm">/ bulan</span>
                    </div>

                    <p class="text-sm font-semibold text-gray-900 mb-1">
                        Mulai rapikan booking bisnismu.
                    </p>
                    <p class="text-xs sm:text-sm text-gray-600 leading-relaxed min-h-[40px]">
                        Semua yang kamu butuhkan untuk mengelola booking dengan lebih rapi dan profesional.
                    </p>

                    <!-- Target Cocok untuk -->
                    <div class="mt-4 rounded-xl p-3 text-xs leading-relaxed bg-slate-100/90 border border-slate-200 text-slate-700">
                        <span class="font-bold text-slate-900">Cocok untuk:</span> bisnis jasa yang baru mulai beralih dari booking manual.
                    </div>

                    <!-- Features -->
                    <div class="mt-6 pt-5 border-t border-gray-100">
                        <p class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-3.5">
                            Semua kebutuhan booking dasar
                        </p>
                        <x-plan-features :plan="$plans->get('small')" plan-key="small" class="text-gray-600 mb-6" />
                    </div>
                </div>

                <div class="mt-auto pt-6">
                    <a href="{{ $smallAction['url'] }}" class="{{ $smallAction['class'] }}">
                        {{ $smallAction['label'] }}
                    </a>
                    @if ($smallAction['show_login'])
                        <div class="mt-2.5 text-center">
                            <a href="{{ route('login') }}" class="text-xs text-slate-500 hover:text-indigo-600 font-medium transition-colors">
                                Sudah punya akun? Masuk
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- CARD 2: Medium (Popular) - Default Active -->
            <div 
                class="pricing-card is-active rounded-[2rem] p-7 sm:p-9 flex-col justify-between relative" 
                :class="activeMobileTab === 1 ? 'flex' : 'hidden lg:flex'" 
                data-index="1">
                <div>
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="text-lg font-bold text-gray-900 tracking-tight">Medium</h3>
                        <span class="bg-[#4F46E5] text-white text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wide flex items-center gap-1 shadow-sm">
                            <svg class="w-3.5 h-3.5 text-amber-300" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            Paling Populer
                        </span>
                    </div>

                    <div class="flex items-baseline mb-4">
                        <span class="text-3xl sm:text-4xl font-extrabold text-gray-900 tracking-tight">Rp{{ number_format($plans->get('medium')?->hargabulanan ?? 299000, 0, ',', '.') }}</span>
                        <span class="text-gray-500 font-medium ml-1.5 text-sm">/ bulan</span>
                    </div>

                    <p class="text-sm font-semibold text-gray-900 mb-1">
                        Bisnismu berkembang, sistemnya ikut berkembang.
                    </p>
                    <p class="text-xs sm:text-sm text-gray-600 leading-relaxed min-h-[40px]">
                        Kelola booking, pelanggan, dan jadwal bisnis dalam satu tempat.
                    </p>

                    <!-- Target Cocok untuk -->
                    <div class="mt-4 rounded-xl p-3 text-xs leading-relaxed bg-indigo-50 border border-indigo-100 text-indigo-950">
                        <span class="font-bold text-indigo-900">Cocok untuk:</span> bisnis jasa dengan tim dan booking yang mulai ramai.
                    </div>

                    <!-- Features -->
                    <div class="mt-6 pt-5 border-t border-gray-100">
                        <p class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-3.5">
                            Lebih banyak kontrol, lebih sedikit pekerjaan manual
                        </p>
                        <x-plan-features :plan="$plans->get('medium')" plan-key="medium" class="text-gray-600 mb-6" />
                    </div>
                </div>

                <div class="mt-auto pt-6">
                    <a href="{{ $mediumAction['url'] }}" class="{{ $mediumAction['class'] }}">
                        {{ $mediumAction['label'] }}
                    </a>
                    @if ($mediumAction['show_login'])
                        <div class="mt-2.5 text-center">
                            <a href="{{ route('login') }}" class="text-xs text-slate-500 hover:text-indigo-600 font-medium transition-colors">
                                Sudah punya akun? Masuk
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- CARD 3: Pro -->
            <div 
                class="pricing-card rounded-[2rem] p-7 sm:p-9 flex-col justify-between" 
                :class="activeMobileTab === 2 ? 'flex' : 'hidden lg:flex'" 
                data-index="2">
                <div>
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="text-lg font-bold text-gray-900 tracking-tight">Pro</h3>
                        <span class="bg-purple-100 text-purple-700 text-xs font-semibold px-3 py-1 rounded-full uppercase tracking-wide">
                            Enterprise
                        </span>
                    </div>

                    <div class="flex items-baseline mb-4">
                        <span class="text-3xl sm:text-4xl font-extrabold text-gray-900 tracking-tight">Rp{{ number_format($plans->get('pro')?->hargabulanan ?? 499000, 0, ',', '.') }}</span>
                        <span class="text-gray-500 font-medium ml-1.5 text-sm">/ bulan</span>
                    </div>

                    <p class="text-sm font-semibold text-gray-900 mb-1">
                        Siap bawa bisnismu naik kelas.
                    </p>
                    <p class="text-xs sm:text-sm text-gray-600 leading-relaxed min-h-[40px]">
                        Booking tanpa batas dengan kontrol, branding, dan fleksibilitas untuk bisnis yang terus berkembang.
                    </p>

                    <!-- Target Cocok untuk -->
                    <div class="mt-4 rounded-xl p-3 text-xs leading-relaxed bg-purple-50 border border-purple-100 text-purple-950">
                        <span class="font-bold text-purple-900">Cocok untuk:</span> bisnis multi-cabang atau bisnis dengan volume booking tinggi.
                    </div>

                    <!-- Features -->
                    <div class="mt-6 pt-5 border-t border-gray-100">
                        <p class="text-xs font-bold uppercase tracking-wider text-purple-600 mb-3.5">
                            Booking tanpa batas & kontrol penuh
                        </p>
                        <x-plan-features :plan="$plans->get('pro')" plan-key="pro" class="text-gray-600 mb-6" />
                    </div>
                </div>

                <div class="mt-auto pt-6">
                    <a href="{{ $proAction['url'] }}" class="{{ $proAction['class'] }}">
                        {{ $proAction['label'] }}
                    </a>
                    @if ($proAction['show_login'])
                        <div class="mt-2.5 text-center">
                            <a href="{{ route('login') }}" class="text-xs text-slate-500 hover:text-indigo-600 font-medium transition-colors">
                                Sudah punya akun? Masuk
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
