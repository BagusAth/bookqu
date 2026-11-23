<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\ListEarlyAccessSignups;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListEarlyAccessSignupsRequest;
use App\Models\EarlyAccessSignup;
use Illuminate\View\View;

/**
 * Early access sign-ups for the platform admin (BQ-175, FR-ADMIN-006).
 */
final class AdminEarlyAccessController extends Controller
{
    public function index(ListEarlyAccessSignupsRequest $request, ListEarlyAccessSignups $listSignups): View
    {
        $search = $request->search();

        return view('admin.early-access', [
            'signups' => $listSignups->execute($search),
            'search' => $search,
            'total' => EarlyAccessSignup::count(),
        ]);
    }
}
