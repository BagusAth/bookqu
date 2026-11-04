<?php

namespace App\Http\Controllers\Owner;

use App\Actions\Owner\UpdateNotificationPreferences;
use App\Actions\Tenant\UpdateBookingPolicy;
use App\Domain\Notifications\NotificationPreferences;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class OwnerBookingPolicyController extends Controller
{
    public function update(Request $request, TenantContext $context, UpdateBookingPolicy $action): RedirectResponse
    {
        $data = $request->validate([
            'refunds_enabled' => ['required', 'boolean'],
            'cancel_before_hours' => ['required', 'integer', 'between:0,8760'],
            'reschedule_before_hours' => ['required', 'integer', 'between:0,8760'],
            'booking_min_lead_hours' => ['required', 'integer', 'between:0,8760'],
            'booking_max_days_ahead' => ['required', 'integer', 'between:1,365'],
            'booking_buffer_minutes' => ['required', 'integer', 'between:0,1440'],
        ]);
        $tenant = $context->tenant();
        abort_if($tenant === null, 403);
        $action->execute($tenant, $data);

        return back()->with('sukses', 'Kebijakan booking disimpan.');
    }

    public function notifications(Request $request, UpdateNotificationPreferences $action): RedirectResponse
    {
        $data = $request->validate([
            'preferences' => ['present', 'array:'.implode(',', NotificationPreferences::EVENTS)],
            'preferences.*' => ['boolean'],
        ]);
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $action->execute($user, array_map(fn ($value) => (bool) $value, $data['preferences']));

        return back()->with('sukses', 'Preferensi notifikasi disimpan.');
    }
}
