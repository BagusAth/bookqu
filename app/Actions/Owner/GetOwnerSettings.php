<?php

declare(strict_types=1);

namespace App\Actions\Owner;

use App\Actions\Integrations\GetOwnerIntegrations;
use App\Actions\Integrations\ManageOwnerApiCredentials;
use App\Actions\Team\GetOwnerTeam;
use App\Actions\Team\ManageTeam;
use App\Domain\Booking\BookingRules;
use App\Domain\Modules\ModuleRelease;
use App\Domain\Subscription\EntitlementRules;
use App\Infrastructure\Integrations\GoogleCalendar;
use App\Models\Tenant;
use App\Models\User;
use App\Policies\TenantResourcePolicy;
use App\Support\CustomerBookingRoutes;

final class GetOwnerSettings
{
    /** @return array<string, mixed> */
    public function execute(Tenant $tenant, ?User $user, string $requestedTab): array
    {
        $policy = app(TenantResourcePolicy::class);
        $canManageBusiness = $user !== null && $policy->ability($user, 'settings.manage');

        $canDeleteBusiness = $user !== null && $policy->ability($user, 'business.delete');
        $canManageTeam = $tenant->exists && $user !== null && $policy->ability($user, 'team.manage');
        $canManageIntegrations = $tenant->exists && $user !== null && $policy->ability($user, 'integrations.manage');
        $accountUser = $user;
        $tabs = $canManageBusiness ? ['profile' => 'Profil Bisnis', 'policy' => 'Kebijakan Booking'] : [];
        $tabs['notifications'] = 'Notifikasi';
        $subscription = $tenant->exists ? $tenant->subscription : null;
        $requestedTab = $requestedTab === 'integrations' ? 'integrasi' : $requestedTab;
        $showIntegrations = $canManageIntegrations && ModuleRelease::state('integrations') === 'released'
            && EntitlementRules::canAccessFeature($subscription, 'integrations')['allowed'];
        $showApi = $canManageIntegrations && ModuleRelease::state('api') === 'released'
            && EntitlementRules::canAccessFeature($subscription, 'api')['allowed'];

        if ($canManageTeam && ModuleRelease::state('team') === 'released') {
            $tabs['tim'] = 'Tim';
        }
        if ($showIntegrations || $showApi) {
            $tabs['integrasi'] = 'Integrasi';
        }
        $tabs['account'] = 'Akun';
        if ($requestedTab === 'tim') {
            abort_unless($canManageTeam, 403);
            abort_unless(isset($tabs['tim']), 404);
        }
        if ($requestedTab === 'integrasi') {
            abort_unless($canManageIntegrations, 403);
            abort_unless(isset($tabs['integrasi']), 403);
        }
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

        $data = compact(
            'tenant', 'accountUser', 'canDeleteBusiness', 'canManageTeam', 'tabs', 'tab',
            'storefrontUrl', 'bookingPolicy', 'notificationEvents', 'notificationPreferences',
        );

        if ($tab === 'tim') {
            $data += app(GetOwnerTeam::class)->execute($tenant, app(ManageTeam::class));
        }
        if ($tab === 'integrasi') {
            if ($showIntegrations) {
                $data += app(GetOwnerIntegrations::class)->execute($tenant, app(GoogleCalendar::class));
            }
            if ($showApi) {
                $data += app(ManageOwnerApiCredentials::class)->overview($tenant);
            }
        }

        return $data;
    }
}
