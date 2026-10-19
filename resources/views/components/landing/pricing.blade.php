@php
    $user = auth()->user();
    $plans = \App\Models\Plan::all()->keyBy(fn ($plan) => strtolower(trim((string) $plan->namapaket)));

    $currentTenant = null;
    if ($user && $user->isOwner()) {
        $sessionTenantId = session('current_tenant_id');
        if ($sessionTenantId) {
            $currentTenant = \App\Models\Tenant::where('id', $sessionTenantId)->where('iduser', $user->id)->first();
        }
        if (!$currentTenant) {
            $currentTenant = $user->tenant ?? \App\Models\Tenant::where('iduser', $user->id)->first();
        }
    }

    $currentSubscription = $currentTenant 
        ? \App\Models\Subscription::withoutGlobalScopes()
            ->where('idtenant', $currentTenant->id)
            ->with('plan')
            ->latest()
            ->first() 
        : null;

    $isActivePaid = $currentSubscription !== null 
        && $currentSubscription->status === \App\Domain\Subscription\SubscriptionState::STATUS_ACTIVE 
        && !\App\Domain\Subscription\SubscriptionState::isExpired($currentSubscription);

    $activePlanName = $isActivePaid 
        ? strtolower(trim((string) ($currentSubscription->plan?->namapaket ?? ''))) 
        : null;

    $activePlan = $activePlanName ? $plans->get($activePlanName) : null;

    $resolvePlanAction = function (string $planKey, string $defaultLabel, string $focusRingColor = 'focus:ring-[#4F46E5]') use ($user, $plans, $activePlanName, $activePlan) {
        $targetPlan = $plans->get(strtolower($planKey));

        // 1. Guest (unauthenticated)
        if (!$user) {
            return [
                'url' => route('register', ['plan' => $planKey]),
                'label' => $defaultLabel,
                'is_active_plan' => false,
                'show_login' => true,
                'class' => "btn-paket w-full py-3.5 px-4 rounded-xl border border-gray-200 text-gray-700 font-semibold text-sm mt-auto focus:outline-none focus:ring-2 {$focusRingColor} text-center block",
            ];
        }

        // 2. Admin
        if ($user->isAdmin()) {
            return [
                'url' => route('admin.dashboard'),
                'label' => 'Buka Dashboard Admin',
                'is_active_plan' => false,
                'show_login' => false,
                'class' => "btn-paket w-full py-3.5 px-4 rounded-xl border border-gray-200 text-gray-700 font-semibold text-sm mt-auto focus:outline-none focus:ring-2 {$focusRingColor} text-center block",
            ];
        }

        // 3. Owner belum verifikasi email
        if ($user->isOwner() && !$user->hasVerifiedEmail()) {
            return [
                'url' => route('verification.notice'),
                'label' => 'Verifikasi Email Anda',
                'is_active_plan' => false,
                'show_login' => false,
                'class' => 'btn-paket w-full py-3.5 px-4 rounded-xl border border-amber-300 bg-amber-50 text-amber-800 font-semibold text-sm mt-auto focus:outline-none focus:ring-2 focus:ring-amber-500 text-center block',
            ];
        }

        // 4. Owner dengan paket ini sedang aktif
        if ($activePlanName !== null && $activePlanName === strtolower($planKey)) {
            return [
                'url' => route('owner.subscription'),
                'label' => '✓ Paket Aktif Anda',
                'is_active_plan' => true,
                'show_login' => false,
                'class' => 'btn-paket-current w-full py-3.5 px-4 rounded-xl border border-emerald-300 bg-emerald-50 text-emerald-800 font-bold text-sm mt-auto text-center block cursor-default shadow-xs',
            ];
        }

        // 5. Owner on trial / upgrade / memilih paket lain
        if ($user->isOwner()) {
            $checkoutUrl = $targetPlan ? route('owner.checkout', $targetPlan->id) : route('owner.subscription');
            $label = $defaultLabel;
            if ($activePlan && $targetPlan) {
                if ($targetPlan->hargabulanan > $activePlan->hargabulanan) {
                    $label = 'Upgrade ke ' . ucfirst($planKey);
                } else {
                    $label = 'Pilih ' . ucfirst($planKey);
                }
            } elseif ($activePlanName !== null) {
                $label = 'Upgrade ke ' . ucfirst($planKey);
            }

            return [
                'url' => $checkoutUrl,
                'label' => $label,
                'is_active_plan' => false,
                'show_login' => false,
                'class' => "btn-paket w-full py-3.5 px-4 rounded-xl border border-gray-200 text-gray-700 font-semibold text-sm mt-auto focus:outline-none focus:ring-2 {$focusRingColor} text-center block",
            ];
        }

        // 6. Logged in customer / other role
        return [
            'url' => route('register', ['plan' => $planKey]),
            'label' => $defaultLabel,
            'is_active_plan' => false,
            'show_login' => false,
            'class' => "btn-paket w-full py-3.5 px-4 rounded-xl border border-gray-200 text-gray-700 font-semibold text-sm mt-auto focus:outline-none focus:ring-2 {$focusRingColor} text-center block",
        ];
    };

    $smallAction = $resolvePlanAction('small', 'Mulai Sekarang', 'focus:ring-[#4F46E5]');
    $mediumAction = $resolvePlanAction('medium', 'Pilih Medium', 'focus:ring-[#4F46E5]');
    $proAction = $resolvePlanAction('pro', 'Pilih Pro', 'focus:ring-purple-600');
@endphp

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
                        <span class="text-3xl sm:text-4xl font-extrabold text-gray-900 tracking-tight">Rp149.000</span>
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
                        <ul class="space-y-2.5 text-xs sm:text-sm text-gray-600 mb-6">
                            <li class="flex items-start gap-2.5">
                                <svg class="w-4 h-4 text-indigo-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <span>&lt;300 booking/bulan</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg class="w-4 h-4 text-indigo-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <span>1 bisnis / 1 lokasi</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg class="w-4 h-4 text-indigo-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <span>5 layanan</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg class="w-4 h-4 text-indigo-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <span>1 admin + 2 staff</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg class="w-4 h-4 text-indigo-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <span>Customer booking page</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg class="w-4 h-4 text-indigo-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <span>Calendar & automatic slots</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg class="w-4 h-4 text-indigo-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <span>Email notification</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg class="w-4 h-4 text-indigo-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <span>Basic dashboard</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg class="w-4 h-4 text-indigo-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <span>Automatic invoice</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg class="w-4 h-4 text-indigo-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <span>Pembayaran via BookQu</span>
                            </li>
                        </ul>
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
                        <span class="text-3xl sm:text-4xl font-extrabold text-gray-900 tracking-tight">Rp299.000</span>
                        <span class="text-gray-500 font-medium ml-1.5 text-sm">/ bulan</span>
                    </div>

                    <p class="text-sm font-semibold text-gray-900 mb-1">
                        Bisnismu berkembang, sistemnya ikut berkembang.
                    </p>
                    <p class="text-xs sm:text-sm text-gray-600 leading-relaxed min-h-[40px]">
                        Kelola booking, customer, tim, dan operasional bisnis dalam satu tempat dengan lebih otomatis.
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
                        <ul class="space-y-2.5 text-xs sm:text-sm text-gray-600 mb-6">
                            <li class="flex items-start gap-2.5 font-medium text-gray-900">
                                <svg class="w-4 h-4 text-indigo-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <span>&lt;500 booking/bulan</span>
                            </li>
                            <li class="flex items-start gap-2.5 font-medium text-indigo-700">
                                <svg class="w-4 h-4 text-indigo-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <span>Semua fitur Small</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg class="w-4 h-4 text-indigo-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <span>Unlimited services</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg class="w-4 h-4 text-indigo-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <span>Hingga 15 staff</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg class="w-4 h-4 text-indigo-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <span>Role management</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg class="w-4 h-4 text-indigo-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <span>Customer CRM</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg class="w-4 h-4 text-indigo-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <span>WhatsApp & email reminders</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg class="w-4 h-4 text-indigo-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <span>Promo & voucher</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg class="w-4 h-4 text-indigo-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <span>Complete analytics</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg class="w-4 h-4 text-indigo-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <span>Export Excel / CSV</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg class="w-4 h-4 text-indigo-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <span>BYOPG + platform payment</span>
                            </li>
                        </ul>
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
                        <span class="text-3xl sm:text-4xl font-extrabold text-gray-900 tracking-tight">Rp499.000</span>
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
                        <ul class="space-y-2.5 text-xs sm:text-sm text-gray-600 mb-6">
                            <li class="flex items-start gap-2.5 font-medium text-gray-900">
                                <svg class="w-4 h-4 text-purple-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <span>Unlimited booking</span>
                            </li>
                            <li class="flex items-start gap-2.5 font-medium text-purple-700">
                                <svg class="w-4 h-4 text-purple-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <span>Semua fitur Medium</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg class="w-4 h-4 text-purple-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <span>Unlimited staff</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg class="w-4 h-4 text-purple-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <span>Multi-location</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg class="w-4 h-4 text-purple-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <span>Custom domain</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg class="w-4 h-4 text-purple-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <span>Landing page builder</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg class="w-4 h-4 text-purple-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <span>Advanced permission</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg class="w-4 h-4 text-purple-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <span>White-label</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg class="w-4 h-4 text-purple-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <span>Advanced analytics</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg class="w-4 h-4 text-purple-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <span>Automation workflow</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg class="w-4 h-4 text-purple-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <span>Priority support</span>
                            </li>
                        </ul>
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
