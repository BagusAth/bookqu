<?php

namespace App\Actions\Booking;

use App\Models\Booking;
use App\Models\Service;
use App\Support\CsvCell;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExportOwnerBookings
{
    public function __construct(private ListOwnerBookings $bookings) {}

    /** @param Builder<Booking> $query */
    public function execute(Builder $query): StreamedResponse
    {
        return response()->streamDownload(function () use ($query): void {
            $file = fopen('php://output', 'w');
            if ($file === false) {
                return;
            }
            fputcsv($file, ['Kode booking', 'Pelanggan', 'Telepon', 'Email', 'Layanan', 'Tanggal', 'Jam', 'Dibayar'], escape: '');
            foreach ($query->lazy(500) as $booking) {
                $service = $booking->layanan;
                fputcsv($file, CsvCell::row([
                    $booking->booking_code, $booking->namapelanggan, $booking->nomorhp, $booking->email,
                    $service instanceof Service ? $service->namalayanan : null, Carbon::parse($booking->tanggalbooking)->toDateString(),
                    $booking->jam, $this->bookings->paidAmount($booking),
                ]), escape: '');
            }
            fclose($file);
        }, 'bookings.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
