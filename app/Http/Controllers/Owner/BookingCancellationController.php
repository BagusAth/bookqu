<?php

namespace App\Http\Controllers\Owner;

use App\Actions\Booking\DecideBookingCancellation;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\TenantMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class BookingCancellationController extends Controller
{
    public function store(Request $request, Booking $booking, DecideBookingCancellation $cancellation): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $actor = $request->attributes->get('tenant_member');
        abort_unless($actor instanceof TenantMember && (int) $actor->idtenant === (int) $booking->idtenant, 404);
        $cancellation->request($booking, $actor, $data['reason']);

        return back()->with('sukses', 'Permintaan pembatalan dikirim.');
    }

    public function decide(Request $request, int $cancellationId, DecideBookingCancellation $cancellation): RedirectResponse
    {
        $data = $request->validate(['decision' => ['required', 'in:approve,reject']]);
        $actor = $request->attributes->get('tenant_member');
        abort_unless($actor instanceof TenantMember, 403);
        $cancellation->decide($cancellationId, $actor, $data['decision'] === 'approve');

        return back()->with('sukses', 'Keputusan pembatalan disimpan.');
    }
}
