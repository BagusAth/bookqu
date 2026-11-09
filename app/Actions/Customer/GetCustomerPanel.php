<?php

declare(strict_types=1);

namespace App\Actions\Customer;

use App\Models\Tenant;
use App\Support\StatusBadge;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

/**
 * The customer panel payload (owner-portal.md §8.5, the contract in components/owner/customer-panel):
 * contact links, figures, the internal note, booking and payment history and the walk-in prefill.
 * Spending figures and payment history are left out when the viewer may not see revenue (AC-PLG-03).
 */
class GetCustomerPanel
{
    public function __construct(private GetCustomerDetail $getCustomerDetail) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(Tenant $tenant, string $identifier, bool $canViewRevenue): array
    {
        $detail = $this->getCustomerDetail->execute($tenant, $identifier);

        /** @var Collection<int, array<string, mixed>> $bookings */
        $bookings = collect($detail['bookings']);
        $phone = $detail['phone'] === '-' ? '' : (string) $detail['phone'];
        $email = $detail['email'] === '-' ? '' : (string) $detail['email'];

        $stats = [['label' => 'Booking', 'value' => (string) $detail['total_bookings']]];
        if ($canViewRevenue) {
            $stats[] = ['label' => 'Total dibayar', 'value' => (string) $detail['formatted_spent']];
        }
        $stats[] = ['label' => 'Dibatalkan', 'value' => (string) $bookings->where('status', 'cancelled')->count()];
        $stats[] = ['label' => 'Tidak datang', 'value' => (string) $bookings->where('status', 'no_show')->count()];

        return [
            'identifier' => (string) $detail['identifier'],
            'name' => (string) $detail['name'],
            'phone' => $phone,
            'whatsapp_url' => self::whatsAppUrl($phone),
            'email' => $email,
            'email_url' => $email !== '' ? 'mailto:'.$email : null,
            'note' => (string) $detail['notes'],
            'note_url' => route('owner.customers.note'),
            'stats' => $stats,
            'bookings' => $bookings->map(fn (array $booking): array => [
                'code' => (string) $booking['code'],
                'service' => (string) $booking['service'],
                'date' => (string) $booking['date'],
                'time' => (string) $booking['time'],
                'status' => array_combine(['label', 'tone'], StatusBadge::for('booking', (string) $booking['status'])),
                'panel_url' => Route::has('owner.bookings.panel') ? route('owner.bookings.panel', $booking['id']) : null,
                'href' => route('owner.bookings', ['katakunci' => $booking['code']]),
            ])->values()->all(),
            'payments' => $canViewRevenue ? collect($detail['payments'])->map(fn (array $payment): array => [
                'order_id' => (string) $payment['order_id'],
                'date' => (string) $payment['date'],
                'amount' => (string) $payment['jumlah'],
                'status' => array_combine(['label', 'tone'], StatusBadge::for('payment', (string) $payment['status'])),
            ])->values()->all() : [],
            // AC-PLG-04: "Buat booking" fills these walk-in form fields.
            'walkin' => ['namapelanggan' => (string) $detail['name'], 'nomorhp' => $phone, 'email' => $email],
        ];
    }

    /**
     * A wa.me link for an Indonesian phone number, or null when the number is too short.
     */
    public static function whatsAppUrl(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62'.$digits;
        }

        return strlen($digits) >= 9 ? 'https://wa.me/'.$digits : null;
    }
}
