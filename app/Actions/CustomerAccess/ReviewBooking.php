<?php

namespace App\Actions\CustomerAccess;

use App\Domain\CustomerAccess\AccessContext;
use App\Domain\CustomerAccess\AccessOperation;
use App\Models\Booking;
use App\Models\BookingLog;
use App\Models\Review;
use Illuminate\Support\Facades\DB;

final class ReviewBooking
{
    /** @param array{rating: int, komentar?: ?string} $data */
    public function execute(AccessContext $context, Booking $booking, array $data): bool
    {
        return DB::transaction(function () use ($context, $booking, $data): bool {
            app(ResolveCustomerAccess::class)->underLock($context, AccessOperation::Review, $booking);
            $booking->refresh();
            if ($booking->status !== 'completed' || Review::where('idbooking', $booking->id)->exists()) {
                return false;
            }
            Review::create([
                'idtenant' => $booking->idtenant, 'idbooking' => $booking->id,
                'rating' => $data['rating'], 'komentar' => $data['komentar'] ?? null, 'is_hidden' => false,
            ]);
            BookingLog::record($booking->id, 'reviewed', 'Customer memberikan ulasan.', ['rating' => $data['rating']]);

            return true;
        });
    }
}
