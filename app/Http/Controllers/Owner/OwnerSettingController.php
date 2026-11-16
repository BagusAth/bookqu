<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Actions\Owner\DeleteOwnerAccount;
use App\Actions\Owner\UpdateOwnerAccount;
use App\Actions\Tenant\CreateInitialProfile;
use App\Actions\Tenant\UpdateBusinessProfile;
use App\Actions\Tenant\UpdatePaymentSettings;
use App\Domain\Booking\BookingRules;
use App\Domain\Subscription\EntitlementRules;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\DeleteOwnerAccountRequest;
use App\Http\Requests\Owner\UpdateOwnerAccountRequest;
use App\Http\Requests\Tenant\StoreInitialProfileRequest;
use App\Http\Requests\Tenant\UpdateBusinessProfileRequest;
use App\Http\Requests\Tenant\UpdatePaymentSettingsRequest;
use App\Models\Tenant;
use App\Policies\TenantResourcePolicy;
use App\Support\CustomerBookingRoutes;
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
    public function index(Request $request): View
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

        $canDeleteBusiness = $user !== null && $policy->ability($user, 'business.delete');
        $canManageTeam = $user !== null && $policy->ability($user, 'team.manage') && config('modules.team') === 'released';
        $accountUser = $user;
        $tabs = $canManageBusiness ? ['profile' => 'Profil Bisnis', 'policy' => 'Kebijakan Booking'] : [];
        $tabs += ['notifications' => 'Notifikasi', 'account' => 'Akun'];
        $requestedTab = $request->string('tab')->toString();
        $tab = isset($tabs[$requestedTab]) ? $requestedTab : array_key_first($tabs);
        $storefrontUrl = $tenant->slug ? CustomerBookingRoutes::url('customer.booking.program', $tenant->slug) : null;

        $bookingPolicy = [
            'refunds_enabled' => (bool) $tenant->refunds_enabled,
            'cancel_before_hours' => $tenant->cancel_before_hours ?? 24,
            'reschedule_before_hours' => $tenant->reschedule_before_hours ?? 24,
            'booking_min_lead_hours' => $tenant->booking_min_lead_hours ?? 0,
            'booking_max_days_ahead' => BookingRules::maximumAdvanceDays($tenant),
            'booking_buffer_minutes' => $tenant->booking_buffer_minutes ?? 0,
        ];
        $notificationEvents = [
            'new_booking' => 'Booking baru', 'payment_success' => 'Pembayaran berhasil',
            'cancellation' => 'Pembatalan', 'reschedule' => 'Pindah jadwal',
            'refund_request' => 'Pengajuan refund', 'new_review' => 'Ulasan baru',
            'payout_status' => 'Status pencairan', 'plan_status' => 'Status paket',
        ];
        $notificationPreferences = $user?->getAttribute('notification_preferences') ?? [];

        return view('owner.settings', compact(
            'tenant', 'accountUser', 'canDeleteBusiness', 'canManageTeam', 'tabs', 'tab',
            'storefrontUrl', 'bookingPolicy', 'notificationEvents', 'notificationPreferences',
        ));
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
