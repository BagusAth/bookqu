<?php

namespace App\Actions\Owner;

use App\Actions\Team\ResolveTenantMembership;
use App\Domain\Modules\ModuleRelease;
use App\Domain\Subscription\EntitlementRules;
use App\Domain\Team\TenantPermission;
use App\Models\Subscription;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Route;

final class GetOwnerNavigation
{
    /** @return list<array<string, mixed>> */
    public function execute(User $user): array
    {
        $member = app(ResolveTenantMembership::class)->execute($user);
        if ($member === null) {
            return [];
        }
        $context = app(TenantContext::class);
        $previous = $context->getTenantId();
        try {
            $context->setTenantId((int) $member->idtenant);
            $subscription = Subscription::with('plan')->where('idtenant', $member->idtenant)->latest()->first();
        } finally {
            $context->setTenantId($previous);
        }
        $items = [];
        foreach (config('owner-navigation', []) as $item) {
            if (! TenantPermission::allowsRole((string) $member->role, $item['ability'])) {
                continue;
            }
            if ($item['module'] !== null && ModuleRelease::state($item['module']) === 'hidden') {
                continue;
            }
            if (! Route::has($item['route'])) {
                continue;
            }
            $locked = $item['feature'] !== null && ! EntitlementRules::canAccessFeature($subscription, $item['feature'])['allowed'];
            if ($item['id'] === 'units' && $locked) {
                continue;
            }
            $item['locked'] = $locked;
            $item['url'] = route($item['route']);
            $items[] = $item;
        }

        return $items;
    }

    /** @return list<array{title: ?string, items: list<array<string, mixed>>}> */
    public function sections(User $user): array
    {
        $groups = [];
        foreach ($this->execute($user) as $item) {
            $key = $item['group'] ?? '';
            if (! isset($groups[$key])) {
                $groups[$key] = ['title' => $item['group'], 'items' => []];
            }
            $groups[$key]['items'][] = ['label' => $item['label'], 'href' => $item['url'], 'route' => [$item['route']], 'icon' => $item['icon'],
                'badge' => $item['locked'] ? 'Terkunci' : null, 'badge_type' => $item['locked'] ? 'lock' : null];
        }

        return array_values($groups);
    }
}
