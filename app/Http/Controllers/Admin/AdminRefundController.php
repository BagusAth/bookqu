<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Payment\ProcessRefundTransfer;
use App\Http\Controllers\Controller;
use App\Models\Refund;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

final class AdminRefundController extends Controller
{
    public function index(): View
    {
        return view('admin.refunds', ['refunds' => Refund::with('refundRequest')->latest('id')->paginate(25)]);
    }

    public function transferDetails(Refund $refund): Response
    {
        $request = $refund->refundRequest;
        abort_unless($refund->status === 'pending' && $request !== null && (int) $request->idpayment === (int) $refund->payment_id, 404);

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
