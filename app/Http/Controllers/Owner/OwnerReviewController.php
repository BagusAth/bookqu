<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Actions\Review\GetReviewsPage;
use App\Actions\Review\ReplyToReview;
use App\Actions\Review\ToggleReviewVisibility;
use App\Http\Controllers\Controller;
use App\Traits\ResolvesOwnerTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OwnerReviewController extends Controller
{
    use ResolvesOwnerTenant;

    /**
     * The Ulasan page (owner-portal.md §8.11).
     */
    public function index(Request $request, GetReviewsPage $getReviewsPage): View
    {
        $tenant = $this->resolveTenant() ?? abort(404, 'Tenant tidak ditemukan.');

        return view('owner.reviews', $getReviewsPage->execute(
            $tenant,
            is_string($request->query('tab')) ? $request->query('tab') : null,
            is_string($request->query('search')) ? $request->query('search') : '',
        ) + ['tenant' => $tenant]);
    }

    public function reply(Request $request, int $id, ReplyToReview $replyToReview): RedirectResponse
    {
        $tenant = $this->resolveTenant() ?? abort(404, 'Tenant tidak ditemukan.');
        $replyToReview->execute($tenant, $id, $request->only('balasan'));

        return redirect()->route('owner.reviews')->with('sukses', 'Balasan ulasan disimpan.');
    }

    public function toggleVisibility(int $id, ToggleReviewVisibility $toggleReviewVisibility): RedirectResponse
    {
        $tenant = $this->resolveTenant() ?? abort(404, 'Tenant tidak ditemukan.');
        $review = $toggleReviewVisibility->execute($tenant, $id);

        return redirect()->route('owner.reviews')
            ->with('sukses', $review->is_hidden ? 'Ulasan disembunyikan dari halaman booking.' : 'Ulasan ditampilkan lagi di halaman booking.');
    }
}
