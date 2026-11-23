<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Integrations\ReadOwnerApi;
use App\Http\Controllers\Controller;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;

final class OwnerDataController extends Controller
{
    public function index(string $resource, TenantContext $context, ReadOwnerApi $action): JsonResponse
    {
        $tenant = $context->tenant() ?? abort(404);

        return response()->json($action->execute($tenant, $resource));
    }
}
