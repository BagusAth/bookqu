<?php

namespace App\Actions\Booking;

use App\Domain\Booking\CheckoutDraft;

final class CheckoutDraftSession
{
    public function read(): CheckoutDraft
    {
        $draft = session('booking', []);

        return new CheckoutDraft(
            $draft['tenant_id'] ?? null,
            $draft['service_id'] ?? null,
            $draft['tanggal'] ?? null,
            $draft['jam'] ?? null,
            $draft['schedule_id'] ?? null,
            $draft['schedule_ids'] ?? null,
        );
    }

    public function start(int $tenantId, int $serviceId): void
    {
        session(['booking' => ['tenant_id' => $tenantId, 'service_id' => $serviceId, 'tanggal' => null, 'jam' => null]]);
    }

    public function bindTenant(int $tenantId): void
    {
        session()->put('booking.tenant_id', $tenantId);
    }

    public function selectDate(int $tenantId, string $date, bool $clearSlots, bool $legacyStep = false): void
    {
        $this->bindTenant($tenantId);
        session()->put('booking.tanggal', $date);
        if ($clearSlots) {
            session()->forget(['booking.jam', 'booking.schedule_id', 'booking.schedule_ids']);
            if ($legacyStep) {
                session()->put('booking.jam', null);
            }
        }
    }

    public function selectSlots(array|string $times, array $ids): void
    {
        session()->put('booking.jam', $times);
        session()->put('booking.schedule_id', $ids[0] ?? null);
        session()->put('booking.schedule_ids', $ids);
    }

    public function clear(): void
    {
        session()->forget('booking');
    }
}
