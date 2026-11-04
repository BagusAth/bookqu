<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Actions\Owner\DeleteOwnerAccount;
use App\Actions\Owner\UpdateOwnerAccount;
use App\Actions\Tenant\CreateInitialProfile;
use App\Actions\Tenant\UpdateBusinessProfile;
use App\Actions\Tenant\UpdatePaymentSettings;
use App\Domain\Subscription\EntitlementRules;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\UpdateOwnerAccountRequest;
use App\Http\Requests\Tenant\StoreInitialProfileRequest;
use App\Http\Requests\Tenant\UpdateBusinessProfileRequest;
use App\Http\Requests\Tenant\UpdatePaymentSettingsRequest;
use App\Models\OwnerPayout;
use App\Models\Tenant;
use App\Policies\TenantResourcePolicy;
use App\Traits\ResolvesOwnerTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OwnerSettingController extends Controller
{
    use ResolvesOwnerTenant;

    /**
     * Halaman pengaturan bisnis.
     */
    public function index(): View
    {
        $tenant = $this->resolveTenant();
        $user = auth()->user();

        if (! $tenant) {
            $tenant = new Tenant;
            if ($user) {
                $tenant->setRelation('user', $user);
            }
        }

        $policy = app(TenantResourcePolicy::class);
        $canManageBusiness = $user !== null && $policy->ability($user, 'settings.manage');
        $canManagePayments = $user !== null && $policy->ability($user, 'payouts.manage');
        $canDeleteBusiness = $user !== null && $policy->ability($user, 'business.delete');
        $canManageTeam = $user !== null && $policy->ability($user, 'team.manage') && config('modules.team') === 'released';
        $accountUser = $user;
        $payouts = collect();
        if ($tenant->id && $canManagePayments) {
            $payouts = OwnerPayout::where('idtenant', $tenant->id)
                ->orderByDesc('requested_at')
                ->limit(10)
                ->get();
        }

        return view('owner.settings', compact('tenant', 'payouts', 'accountUser', 'canManageBusiness', 'canDeleteBusiness', 'canManageTeam'));
    }

    /**
     * Simpan profil bisnis untuk owner baru.
     */
    public function storeProfile(StoreInitialProfileRequest $request, CreateInitialProfile $createInitialProfile): RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(403, 'User tidak ditemukan.');
        }

        $tenant = $this->resolveTenant();
        $tenant = $createInitialProfile->execute($user, $request->validated(), $tenant);

        session()->put('current_tenant_id', $tenant->id);

        return redirect()->route('owner.dashboard')->with('sukses', EntitlementRules::hasEffectivePlan($tenant->subscription)
            ? 'Profil bisnis berhasil dibuat. Anda mendapatkan Free Trial 7 Hari paket Pro!'
            : 'Profil bisnis berhasil dibuat. Silakan pilih paket untuk menerima booking.');
    }

    public function updateBusinessProfile(UpdateBusinessProfileRequest $request, UpdateBusinessProfile $updateBusinessProfile): RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(403, 'User tidak ditemukan.');
        }

        $tenant = $this->resolveTenant();
        $tenant = $updateBusinessProfile->execute(
            $user,
            $tenant,
            $request->validated(),
            $request->file('logo')
        );

        session()->put('current_tenant_id', $tenant->id);

        return redirect()->route('owner.settings')->with('sukses', 'Profil bisnis berhasil diperbarui.');
    }

    public function updateAccount(UpdateOwnerAccountRequest $request, UpdateOwnerAccount $updateOwnerAccount): RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(403, 'User tidak ditemukan.');
        }

        $updateOwnerAccount->execute($user, $request->validated());

        return redirect()->route('owner.settings')->with('sukses', 'Akun berhasil diperbarui.');
    }

    public function updatePaymentSettings(UpdatePaymentSettingsRequest $request, UpdatePaymentSettings $updatePaymentSettings): RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(403, 'User tidak ditemukan.');
        }

        $tenant = $this->resolveTenant();
        if (! $tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $updatePaymentSettings->execute($tenant, $request->validated());

        return redirect()->route('owner.settings')->with('sukses', 'Pengaturan pembayaran berhasil diperbarui.');
    }

    /**
     * Hapus akun dan seluruh data bisnis owner secara permanen setelah konfirmasi.
     */
    public function deleteAccount(Request $request, DeleteOwnerAccount $deleteOwnerAccount): RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(403, 'User tidak ditemukan.');
        }

        $tenant = $this->resolveTenant();

        $deleteOwnerAccount->execute(
            $user,
            $tenant,
            (string) $request->input('confirm_account', '')
        );

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('sukses', 'Akun dan bisnis Anda telah berhasil dihapus secara permanen.');
    }
}
