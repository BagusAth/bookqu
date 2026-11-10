<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Actions\Owner\ListWalkInSlots;
use App\Actions\Owner\SearchOwnerRecords;
use App\Http\Controllers\Controller;
use App\Traits\ResolvesOwnerTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * JSON endpoints of the owner app shell (BQ-128): global search and the walk-in slot list.
 */
class OwnerShellController extends Controller
{
    use ResolvesOwnerTenant;

    public function search(Request $request, SearchOwnerRecords $search): JsonResponse
    {
        $validated = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $tenant = $this->resolveTenant();
        abort_unless($tenant !== null, 404);

        return response()->json($search->execute($tenant, (string) ($validated['q'] ?? '')));
    }

    public function walkInSlots(Request $request, ListWalkInSlots $listWalkInSlots): JsonResponse
    {
        $validated = $request->validate(['date' => ['required', 'date_format:Y-m-d']]);
        $tenant = $this->resolveTenant();
        abort_unless($tenant !== null, 404);

        return response()->json(['slots' => $listWalkInSlots->execute($tenant, $validated['date'])]);
    }
}
