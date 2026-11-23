<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Models\EarlyAccessSignup;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Early access sign-ups for the platform admin, newest first, optionally filtered by name,
 * business or email (BQ-175, FR-ADMIN-006).
 */
class ListEarlyAccessSignups
{
    public const PER_PAGE = 25;

    /**
     * @return LengthAwarePaginator<int, EarlyAccessSignup>
     */
    public function execute(string $search = ''): LengthAwarePaginator
    {
        $query = EarlyAccessSignup::query()->latest()->latest('id');

        if ($search !== '') {
            $pattern = '%'.addcslashes($search, '\%_').'%';
            $query->where(function ($matches) use ($pattern): void {
                $matches->where('full_name', 'like', $pattern)
                    ->orWhere('business_name', 'like', $pattern)
                    ->orWhere('email', 'like', $pattern);
            });
        }

        return $query->paginate(self::PER_PAGE)->withQueryString();
    }
}
