<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;
use Throwable;

/**
 * The one period filter of Laporan (owner-portal.md §8.13): this month, last month, the last 3, 6
 * or 12 months (including this month), or a custom range of at most 366 days. An unknown or
 * invalid choice falls back to this month.
 */
final readonly class ReportPeriod
{
    public const PRESETS = [
        'bulan-ini' => 'Bulan ini',
        'bulan-lalu' => 'Bulan lalu',
        '3-bulan' => '3 bulan terakhir',
        '6-bulan' => '6 bulan terakhir',
        '12-bulan' => '12 bulan terakhir',
        'kustom' => 'Pilih tanggal',
    ];

    private const MAX_DAYS = 366;

    private function __construct(
        public string $key,
        public CarbonImmutable $start,
        public CarbonImmutable $end,
    ) {}

    /**
     * @param  array<string, mixed>  $query
     */
    public static function fromQuery(array $query): self
    {
        $today = CarbonImmutable::now();
        $key = is_string($query['periode'] ?? null) ? $query['periode'] : 'bulan-ini';

        return match ($key) {
            'bulan-lalu' => new self($key, $today->subMonthNoOverflow()->startOfMonth(), $today->subMonthNoOverflow()->endOfMonth()),
            '3-bulan' => new self($key, $today->subMonthsNoOverflow(2)->startOfMonth(), $today->endOfMonth()),
            '6-bulan' => new self($key, $today->subMonthsNoOverflow(5)->startOfMonth(), $today->endOfMonth()),
            '12-bulan' => new self($key, $today->subMonthsNoOverflow(11)->startOfMonth(), $today->endOfMonth()),
            'kustom' => self::custom($query) ?? self::thisMonth($today),
            default => self::thisMonth($today),
        };
    }

    private static function thisMonth(CarbonImmutable $today): self
    {
        return new self('bulan-ini', $today->startOfMonth(), $today->endOfMonth());
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private static function custom(array $query): ?self
    {
        $from = $query['dari'] ?? null;
        $to = $query['sampai'] ?? null;
        if (! is_string($from) || ! is_string($to) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            return null;
        }

        try {
            $start = CarbonImmutable::createFromFormat('!Y-m-d', $from);
            $end = CarbonImmutable::createFromFormat('!Y-m-d', $to);
        } catch (Throwable) {
            return null;
        }

        if (! $start instanceof CarbonImmutable || ! $end instanceof CarbonImmutable || $start->format('Y-m-d') !== $from
            || $end->format('Y-m-d') !== $to || $end->lt($start) || $start->diffInDays($end) >= self::MAX_DAYS) {
            return null;
        }

        return new self('kustom', $start->startOfDay(), $end->endOfDay());
    }

    /**
     * Whether the period is short enough to be shown per day instead of per month.
     */
    public function isDaily(): bool
    {
        return $this->start->diffInDays($this->end) < 62;
    }

    public function label(): string
    {
        if ($this->key !== 'kustom') {
            return self::PRESETS[$this->key];
        }

        return $this->start->translatedFormat('j M Y').' – '.$this->end->translatedFormat('j M Y');
    }

    /**
     * The query string that selects this period again (links between tabs and the export).
     *
     * @return array<string, string>
     */
    public function query(): array
    {
        return $this->key === 'kustom'
            ? ['periode' => 'kustom', 'dari' => $this->start->format('Y-m-d'), 'sampai' => $this->end->format('Y-m-d')]
            : ['periode' => $this->key];
    }
}
