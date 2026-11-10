<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Actions\Customer\GetCustomerDetail;
use App\Actions\Customer\GetCustomerPanel;
use App\Actions\Customer\ListCustomers;
use App\Actions\Customer\SaveCustomerNote;
use App\Actions\Team\FilterRevenueFigures;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\SaveCustomerNoteRequest;
use App\Traits\ResolvesOwnerTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OwnerCustomerController extends Controller
{
    use ResolvesOwnerTenant;

    /**
     * The Pelanggan page (owner-portal.md §8.5): summary, quick filters, search and the directory.
     */
    public function index(Request $request, ListCustomers $listCustomers): View
    {
        $tenant = $this->resolveTenant() ?? abort(404, 'Tenant tidak ditemukan.');
        $canViewRevenue = $request->attributes->get('can_view_revenue') !== false;

        $page = $listCustomers->execute(
            $tenant,
            (string) $request->query('search', ''),
            is_string($request->query('filter')) ? $request->query('filter') : null,
            $canViewRevenue,
        );

        return view('owner.customers', $page + ['tenant' => $tenant, 'canViewRevenue' => $canViewRevenue]);
    }

    /**
     * Customer panel payload (the shared customer panel, BQ-129 contract).
     */
    public function panel(Request $request, GetCustomerPanel $getCustomerPanel): JsonResponse
    {
        $tenant = $this->resolveTenant() ?? abort(404, 'Tenant tidak ditemukan.');

        return response()->json($getCustomerPanel->execute(
            $tenant,
            (string) $request->query('identifier', ''),
            $request->attributes->get('can_view_revenue') !== false,
        ));
    }

    /**
     * Customer detail — JSON endpoint kept for existing integrations.
     * IDOR-protected: identifier must belong to this tenant's bookings.
     */
    public function show(Request $request, GetCustomerDetail $getCustomerDetail): JsonResponse
    {
        $tenant = $this->resolveTenant() ?? abort(404, 'Tenant tidak ditemukan.');

        $data = $getCustomerDetail->execute($tenant, (string) $request->input('identifier', ''));

        if ($request->attributes->get('can_view_revenue') === false) {
            $data = app(FilterRevenueFigures::class)->execute($data);
        }

        return response()->json($data);
    }

    /**
     * Save or update an internal owner note for a customer.
     * Tenant-scoped — IDOR protected before write.
     */
    public function saveNote(SaveCustomerNoteRequest $request, SaveCustomerNote $saveCustomerNote): JsonResponse
    {
        $tenant = $this->resolveTenant() ?? abort(404, 'Tenant tidak ditemukan.');

        $validated = $request->validated();
        $saveCustomerNote->execute($tenant, $validated['customer_identifier'], $validated['notes'] ?? null);

        return response()->json(['success' => true, 'message' => 'Catatan berhasil disimpan.']);
    }
}
