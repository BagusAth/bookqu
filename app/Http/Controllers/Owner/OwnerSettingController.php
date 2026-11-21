<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Actions\Owner\DeleteOwnerAccount;
use App\Actions\Owner\GetOwnerSettings;
use App\Actions\Owner\UpdateOwnerAccount;
use App\Actions\Tenant\CreateInitialProfile;
use App\Actions\Tenant\UpdateBusinessProfile;
use App\Domain\Subscription\EntitlementRules;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\DeleteOwnerAccountRequest;
use App\Http\Requests\Owner\UpdateOwnerAccountRequest;
use App\Http\Requests\Tenant\StoreInitialProfileRequest;
use App\Http\Requests\Tenant\UpdateBusinessProfileRequest;
use App\Models\Tenant;
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
    public function index(Request $request, GetOwnerSettings $settings): View
    {
        return view('owner.settings', $settings->execute(
            $this->resolveTenant() ?? new Tenant,
            $request->user(),
            $request->string('tab')->toString(),
        ));
    }

    public function legacyBusiness(Request $request): RedirectResponse
    {
        return redirect()->route('owner.settings', $request->query(), 301);
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

        $emailChanged = strcasecmp(trim((string) $request->validated('email')), trim((string) $user->email)) !== 0;
        $updateOwnerAccount->execute($user, $request->validated(), $request->session()->getId());

        if ($request->filled('password')) {
            $request->session()->regenerate();
        }

        return redirect()->route('owner.settings')->with('sukses', $emailChanged
            ? 'Akun berhasil diperbarui. Cek email baru Anda untuk verifikasi.'
            : 'Akun berhasil diperbarui.');
    }

    /**
     * Hapus akun dan seluruh data bisnis owner secara permanen setelah konfirmasi.
     */
    public function deleteAccount(DeleteOwnerAccountRequest $request, DeleteOwnerAccount $deleteOwnerAccount): RedirectResponse
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
