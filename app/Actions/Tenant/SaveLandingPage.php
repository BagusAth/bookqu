<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Models\Tenant;
use App\Models\TenantLandingPage;

/**
 * Saves the landing page of a Pro business and publishes or hides it (BQ-154, FR-PUBLIC-007).
 * Saving keeps the current published state, so a published page updates in place.
 */
class SaveLandingPage
{
    public const ACTIONS = ['save', 'publish', 'unpublish'];

    /**
     * @param  array{template: string, headline?: ?string, sections?: ?list<string>, banner_asset_id?: int|string|null}  $data  validated input
     */
    public function execute(Tenant $tenant, array $data, string $action): TenantLandingPage
    {
        $landing = TenantLandingPage::query()->firstOrNew(['idtenant' => $tenant->id]);
        $headline = trim((string) ($data['headline'] ?? ''));

        $landing->fill([
            'template' => $data['template'],
            'headline' => $headline !== '' ? $headline : null,
            'sections' => array_values(array_unique($data['sections'] ?? [])),
            'banner_asset_id' => isset($data['banner_asset_id']) && $data['banner_asset_id'] !== '' ? (int) $data['banner_asset_id'] : null,
        ]);

        $landing->published_at = match ($action) {
            'publish' => $landing->published_at ?? now(),
            'unpublish' => null,
            default => $landing->published_at,
        };
        $landing->save();

        return $landing;
    }
}
