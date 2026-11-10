<?php

declare(strict_types=1);

namespace App\Actions\Review;

use App\Models\Review;
use App\Models\Tenant;
use App\Support\TenantContext;

/**
 * Hides a review from, or shows it again on, the booking page (owner-portal.md §8.11, AC-ULS-01).
 */
class ToggleReviewVisibility
{
    public function execute(Tenant $tenant, int $reviewId): Review
    {
        app(TenantContext::class)->setTenantId($tenant->id);

        $review = Review::where('idtenant', $tenant->id)->findOrFail($reviewId);
        $review->update(['is_hidden' => ! $review->is_hidden]);

        return $review;
    }
}
