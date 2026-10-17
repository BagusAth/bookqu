<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Actions\Calendar\GetOwnerCalendarData;
use App\Http\Controllers\Controller;
use App\Traits\ResolvesOwnerTenant;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OwnerCalendarController extends Controller
{
    use ResolvesOwnerTenant;

    public function index(Request $request, GetOwnerCalendarData $getOwnerCalendarData): View
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $data = $getOwnerCalendarData->execute($tenant, $request);

        return view('owner.calendar', $data);
    }
}
