<?php

declare(strict_types=1);

namespace App\Actions\Review;

use App\Models\Review;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Route;

/**
 * The Ulasan page (owner-portal.md §8.11): average and distribution from aggregates, the tabs
 * Belum dibalas, Semua and Disembunyikan, search, and a paginated list (BQ-101 finding 1).
 */
class GetReviewsPage
{
    public const TABS = ['belum' => 'Belum dibalas', 'semua' => 'Semua', 'disembunyikan' => 'Disembunyikan'];

    public const PER_PAGE = 15;

    /** Suggested reply sentences, by the tone of the review. */
    public const SUGGESTIONS = [
        'positive' => [
            'Terima kasih banyak atas ulasannya! Senang sekali Anda puas dengan layanan kami.',
            'Terima kasih sudah mempercayakan kepada kami. Sampai jumpa di kunjungan berikutnya!',
            'Ulasan Anda sangat berarti bagi tim kami. Terima kasih dan semoga hari Anda menyenangkan.',
        ],
        'negative' => [
            'Terima kasih atas masukannya, dan mohon maaf atas pengalaman yang kurang menyenangkan.',
            'Kami akan memperbaiki hal ini. Boleh hubungi kami agar kami bisa membantu lebih lanjut?',
            'Masukan Anda sudah kami teruskan ke tim. Semoga kami bisa melayani lebih baik di kunjungan berikutnya.',
        ],
    ];

    /**
     * @return array{
     *     reviews: LengthAwarePaginator<int, array<string, mixed>>,
     *     summary: array{total: int, average: float, distribution: array<int, int>},
     *     tabCounts: array<string, int>,
     *     activeTab: string,
     *     search: string,
     *     suggestions: array<string, list<string>>,
     * }
     */
    public function execute(Tenant $tenant, ?string $tab, string $search): array
    {
        app(TenantContext::class)->setTenantId($tenant->id);

        $activeTab = array_key_exists((string) $tab, self::TABS) ? (string) $tab : 'belum';
        $search = trim($search);

        $reviews = $this->filtered($tenant, $activeTab, $search)
            ->with(['booking:id,idtenant,idlayanan,booking_code,namapelanggan,tanggalbooking', 'booking.layanan:id,namalayanan'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (Review $review): array => $this->row($review));

        return [
            'reviews' => $reviews,
            'summary' => $this->summary($tenant),
            'tabCounts' => [
                'belum' => $this->unanswered(Review::where('idtenant', $tenant->id))->count(),
                'semua' => Review::where('idtenant', $tenant->id)->count(),
                'disembunyikan' => Review::where('idtenant', $tenant->id)->where('is_hidden', true)->count(),
            ],
            'activeTab' => $activeTab,
            'search' => $search,
            'suggestions' => self::SUGGESTIONS,
        ];
    }

    /**
     * @return Builder<Review>
     */
    private function filtered(Tenant $tenant, string $tab, string $search): Builder
    {
        $query = Review::where('idtenant', $tenant->id);

        if ($tab === 'belum') {
            // AC-ULS-02: exactly the reviews without a reply, hidden ones included.
            $this->unanswered($query);
        } elseif ($tab === 'disembunyikan') {
            $query->where('is_hidden', true);
        }

        if ($search !== '') {
            $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';
            $query->where(function (Builder $inner) use ($like): void {
                $inner->where('komentar', 'like', $like)
                    ->orWhere('balasan', 'like', $like)
                    ->orWhereHas('booking', fn (Builder $booking) => $booking->where('namapelanggan', 'like', $like)->orWhere('booking_code', 'like', $like));
            });
        }

        return $query;
    }

    /**
     * @param  Builder<Review>  $query
     * @return Builder<Review>
     */
    private function unanswered(Builder $query): Builder
    {
        return $query->where(fn (Builder $inner) => $inner->whereNull('balasan')->orWhere('balasan', ''));
    }

    /**
     * @return array{total: int, average: float, distribution: array<int, int>}
     */
    private function summary(Tenant $tenant): array
    {
        $counts = Review::where('idtenant', $tenant->id)
            ->selectRaw('rating, COUNT(*) AS total')
            ->groupBy('rating')
            ->pluck('total', 'rating');

        $distribution = [];
        foreach ([5, 4, 3, 2, 1] as $stars) {
            $distribution[$stars] = (int) ($counts[$stars] ?? 0);
        }
        $total = array_sum($distribution);
        $weighted = array_sum(array_map(fn (int $stars, int $count): int => $stars * $count, array_keys($distribution), $distribution));

        return [
            'total' => $total,
            'average' => $total > 0 ? round($weighted / $total, 1) : 0.0,
            'distribution' => $distribution,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function row(Review $review): array
    {
        $booking = $review->booking;
        $code = (string) ($booking?->booking_code ?: ($booking ? 'BKQ-'.$booking->id : ''));

        return [
            'id' => (int) $review->id,
            'rating' => (int) $review->rating,
            'comment' => (string) ($review->komentar ?? ''),
            'reply' => (string) ($review->balasan ?? ''),
            'repliedAt' => $review->dibalas_pada?->translatedFormat('j M Y'),
            'createdAt' => $review->created_at?->translatedFormat('j M Y'),
            'hidden' => (bool) $review->is_hidden,
            'customer' => (string) ($booking->namapelanggan ?? 'Pelanggan'),
            'service' => (string) ($booking?->layanan->namalayanan ?? ''),
            'bookingCode' => $code,
            // The booking opens in the booking panel once BQ-135 adds it; until then the filtered list.
            'bookingUrl' => $booking === null ? null : route('owner.bookings', ['katakunci' => $code]),
            'panelUrl' => $booking !== null && Route::has('owner.bookings.panel') ? route('owner.bookings.panel', $booking->id) : null,
            'tone' => (int) $review->rating >= 4 ? 'positive' : 'negative',
        ];
    }
}
