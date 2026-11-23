<?php

declare(strict_types=1);

namespace App\Actions\Review;

use App\Models\Review;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Saves or edits the business's public reply to a review (owner-portal.md §8.11, FR-REVIEW-006).
 * The review is looked up first, so a review of another business answers 404 before validation.
 */
class ReplyToReview
{
    public const MAX_LENGTH = 1000;

    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function execute(Tenant $tenant, int $reviewId, array $input): Review
    {
        app(TenantContext::class)->setTenantId($tenant->id);

        $review = Review::where('idtenant', $tenant->id)->findOrFail($reviewId);

        $validated = Validator::make($input, [
            'balasan' => ['required', 'string', 'max:'.self::MAX_LENGTH],
        ], [], ['balasan' => 'balasan'])->validate();

        $review->update([
            'balasan' => trim((string) $validated['balasan']),
            'dibalas_pada' => now(),
        ]);

        return $review;
    }
}
