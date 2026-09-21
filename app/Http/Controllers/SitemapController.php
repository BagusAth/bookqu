<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class SitemapController extends Controller
{
    /**
     * Generate dynamic XML sitemap for BookQu.
     */
    public function index(): Response
    {
        // Query active, publicly accessible tenants:
        // 1. Must have a valid, non-empty slug.
        // 2. Must belong to an existing user account.
        // 3. Must not have an expired or cancelled latest subscription.
        //    (Direct subquery to bypass TenantScope on Subscription model)
        $tenants = Tenant::query()
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->whereHas('user')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('subscriptions')
                    ->whereColumn('subscriptions.idtenant', 'tenants.id')
                    ->whereRaw('subscriptions.id = (select max(s2.id) from subscriptions s2 where s2.idtenant = tenants.id)')
                    ->where(function ($sub) {
                        $sub->whereIn('subscriptions.status', ['expired', 'cancelled'])
                            ->orWhere(function ($s) {
                                $s->where('subscriptions.status', 'trial')
                                    ->whereNotNull('subscriptions.trial_berakhir')
                                    ->where('subscriptions.trial_berakhir', '<', now());
                            })
                            ->orWhere(function ($s) {
                                $s->where('subscriptions.status', 'active')
                                    ->whereNotNull('subscriptions.langganan_berakhir')
                                    ->where('subscriptions.langganan_berakhir', '<', now());
                            });
                    });
            })
            ->orderBy('updated_at', 'desc')
            ->get(['id', 'namabisnis', 'slug', 'updated_at']);

        $content = view('sitemap', [
            'tenants' => $tenants,
        ])->render();

        return response($content, 200)
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }
}
