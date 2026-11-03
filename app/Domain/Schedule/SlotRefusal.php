<?php

declare(strict_types=1);

namespace App\Domain\Schedule;

/**
 * Why a schedule slot cannot be claimed (ADR-0005). The same reasons drive the
 * advisory availability status shown to customers and owners.
 */
enum SlotRefusal: string
{
    case NotFound = 'not-found';
    case ForeignTenant = 'foreign-tenant';
    case ForeignService = 'foreign-service';
    case WrongDate = 'wrong-date';
    case Blocked = 'blocked';
    case NotOpen = 'not-open';
    case DateClosed = 'date-closed';
    case Past = 'past';
    case ServiceUnavailable = 'service-unavailable';
    case Occupied = 'occupied';
    case InconsistentGroup = 'inconsistent-group';

    /**
     * Advisory status for listings: AVAILABLE, BOOKED, BLOCKED or UNAVAILABLE.
     */
    public function availabilityStatus(): string
    {
        return match ($this) {
            self::Blocked, self::DateClosed => AvailabilityRules::STATUS_BLOCKED,
            self::Occupied, self::InconsistentGroup => AvailabilityRules::STATUS_BOOKED,
            default => AvailabilityRules::STATUS_UNAVAILABLE,
        };
    }

    /**
     * Customer-facing message; `$time` is the slot start (HH:MM).
     */
    public function message(string $time = ''): string
    {
        return match ($this) {
            self::NotFound => 'Satu atau lebih jadwal tidak ditemukan atau tidak tersedia.',
            self::ForeignTenant => 'Jadwal tidak sesuai dengan unit bisnis terpilih.',
            self::ForeignService => 'Jadwal tidak sesuai dengan paket layanan terpilih.',
            self::WrongDate => 'Tanggal jadwal tidak sesuai dengan tanggal pemesanan.',
            self::Blocked, self::NotOpen => "Jadwal untuk jam {$time} tidak tersedia.",
            self::DateClosed => 'Tanggal ini ditutup oleh pengelola. Silakan pilih tanggal lain.',
            self::Past => "Waktu sesi {$time} sudah terlewat (WIB). Silakan pilih waktu lain.",
            self::ServiceUnavailable => 'Layanan ini sedang tidak tersedia. Silakan hubungi pengelola.',
            self::Occupied => "Slot waktu {$time} sudah dibooking oleh pelanggan lain. Silakan pilih waktu lain.",
            self::InconsistentGroup => "Slot waktu {$time} sedang diperiksa oleh sistem. Silakan pilih waktu lain.",
        };
    }
}
