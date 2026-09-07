<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Traits\ResolvesOwnerTenant;
use Illuminate\Http\Request;

class OwnerReviewController extends Controller
{
    use ResolvesOwnerTenant;

    public function index(Request $request)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $search = $request->input('search', '');
        $ratingFilter = $request->input('rating', 'all');

        $query = Review::where('idtenant', $tenant->id)
            ->with(['booking.layanan'])
            ->orderByDesc('created_at');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('komentar', 'like', "%{$search}%")
                  ->orWhere('balasan', 'like', "%{$search}%")
                  ->orWhereHas('booking', function ($bq) use ($search) {
                      $bq->where('namapelanggan', 'like', "%{$search}%");
                  });
            });
        }

        if ($ratingFilter !== 'all' && is_numeric($ratingFilter)) {
            $query->where('rating', (int) $ratingFilter);
        }

        $reviews = $query->get();

        // Calculate aggregate statistics for tenant
        $allTenantReviews = Review::where('idtenant', $tenant->id)->get();
        $totalReviews = $allTenantReviews->count();
        $avgRating = $totalReviews > 0 ? round($allTenantReviews->avg('rating'), 1) : 0;

        $ratingCounts = [
            5 => $allTenantReviews->where('rating', 5)->count(),
            4 => $allTenantReviews->where('rating', 4)->count(),
            3 => $allTenantReviews->where('rating', 3)->count(),
            2 => $allTenantReviews->where('rating', 2)->count(),
            1 => $allTenantReviews->where('rating', 1)->count(),
        ];

        return view('owner.owner-reviews', compact(
            'tenant',
            'reviews',
            'totalReviews',
            'avgRating',
            'ratingCounts',
            'search',
            'ratingFilter'
        ));
    }

    public function reply(Request $request, int $id)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $review = Review::where('idtenant', $tenant->id)->findOrFail($id);

        $validated = $request->validate([
            'balasan' => 'required|string|max:1000',
        ]);

        $review->update([
            'balasan'     => $validated['balasan'],
            'dibalas_pada'=> now(),
        ]);

        return redirect()->route('owner.reviews')
            ->with('sukses', 'Balasan ulasan berhasil disimpan!');
    }

    public function toggleVisibility(int $id)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $review = Review::where('idtenant', $tenant->id)->findOrFail($id);
        $review->update([
            'is_hidden' => !$review->is_hidden,
        ]);

        $statusText = $review->is_hidden ? 'disembunyikan dari publik' : 'ditampilkan kembali ke publik';
        return redirect()->route('owner.reviews')
            ->with('sukses', 'Ulasan berhasil ' . $statusText . '.');
    }
}
