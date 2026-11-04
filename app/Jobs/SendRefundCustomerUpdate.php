<?php

namespace App\Jobs;

use App\Models\Booking;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

final class SendRefundCustomerUpdate implements ShouldQueue
{
    use Queueable;

    public function __construct(private int $tenantId, private int $bookingId, private string $event) {}

    public function handle(): void
    {
        // scope-bypass: a queued notification is bound to its stored booking and tenant.
        $booking = Booking::withoutGlobalScopes()->where('idtenant', $this->tenantId)->find($this->bookingId);
        if ($booking === null || ! $booking->email) {
            return;
        }
        $message = match ($this->event) {
            'accepted_full' => 'Permintaan refund penuh disetujui. Transfer akan diproses oleh BookQu.',
            'accepted_half' => 'Permintaan refund 50% disetujui. Transfer akan diproses oleh BookQu.',
            'rejected' => 'Permintaan refund ditolak. Booking Anda masih berlaku.',
            'processed' => 'Transfer refund Anda telah diproses.',
            default => 'Transfer refund belum berhasil. Tim BookQu dapat mencoba kembali.',
        };

        Mail::raw($message, fn ($mail) => $mail->to($booking->email)->subject('Informasi refund BookQu'));
    }
}
