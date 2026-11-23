<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Payment\ProcessRefundTransfer;
use App\Http\Controllers\Controller;
use App\Models\Refund;
use App\Models\RefundRequest;
use App\Models\Scopes\TenantScope;
use App\Support\TenantContext;
use App\Support\TenantDiscovery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

final class AdminRefundController extends Controller
{
    public function index(): View
    {
        $refunds = Refund::with([
            // scope-bypass: the platform-admin read model loads the request referenced by each listed refund.
            'refundRequest' => fn ($query) => $query->withoutGlobalScope(TenantScope::class)
                ->whereExists(fn ($payment) => $payment->selectRaw('1')->from('payments')
                    ->whereColumn('payments.id', 'refund_requests.idpayment')
                    ->whereColumn('payments.idtenant', 'refund_requests.idtenant')),
        ])->latest('id')->paginate(25);
        $refunds->each(function (Refund $refund): void {
            if ((int) $refund->refundRequest?->idpayment !== (int) $refund->payment_id) {
                $refund->setRelation('refundRequest', null);
            }
        });

        return view('admin.refunds', ['refunds' => $refunds]);
    }

    public function transferDetails(Refund $refund): Response
    {
        abort_unless($refund->status === 'pending', 404);
        $tenantId = app(TenantDiscovery::class)->payment((int) $refund->payment_id) ?? abort(404);
        $request = app(TenantContext::class)->run($tenantId, fn (): RefundRequest => $refund->refundRequest()
            ->where('idpayment', $refund->payment_id)->firstOrFail());

        return response()->view('admin.refund-transfer', ['refund' => $refund, 'account' => $request])
            ->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer');
    }

    public function update(Request $request, Refund $refund, ProcessRefundTransfer $action): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:processed,failed,pending'],
            'reference' => ['nullable', 'required_if:status,processed', 'string', 'max:100'],
            'reason' => ['nullable', 'required_if:status,failed', 'string', 'max:500'],
        ]);
        $action->execute($refund, $data['status'], $data['reference'] ?? null, $data['reason'] ?? null);

        return back()->with('sukses', 'Status refund diperbarui.');
    }
}
