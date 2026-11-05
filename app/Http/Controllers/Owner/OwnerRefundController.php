<?php

namespace App\Http\Controllers\Owner;

use App\Actions\Payment\DecideRefundRequest;
use App\Domain\Payment\RefundRequestState;
use App\Http\Controllers\Controller;
use App\Models\TenantMember;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class OwnerRefundController extends Controller
{
    public function decide(Request $request, int $refundRequestId, DecideRefundRequest $action): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['accepted_full', 'accepted_half', 'rejected'])],
            'reason' => ['nullable', 'required_if:decision,rejected', 'string', 'max:500'],
        ]);
        $member = $request->attributes->get('tenant_member');
        $user = $request->user();
        abort_unless($member instanceof TenantMember && $user instanceof User, 403);
        $action->execute((int) $member->idtenant, $refundRequestId, $user, RefundRequestState::from($data['decision']), $data['reason'] ?? null);

        return back()->with('sukses', 'Keputusan refund disimpan.');
    }
}
