<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Actions\Subscription\GetSubscriptionOverview;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Traits\ResolvesOwnerTenant;

class OwnerSubscriptionController extends Controller
{
    use ResolvesOwnerTenant;

    /**
     * Halaman subscription management.
     */
    public function index(GetSubscriptionOverview $getOverview)
    {
        $tenant = $this->resolveTenant();
        $user = auth()->user();

        if (!$tenant) {
            $tenant = new Tenant();
            if ($user) {
                $tenant->setRelation('user', $user);
            }
            $overview = $getOverview->execute(null);
        } else {
            $overview = $getOverview->execute($tenant);
        }

        return view('owner.subscription', array_merge(['tenant' => $tenant], $overview));
    }
}


