<?php

namespace App\Actions\Team;

final class FilterRevenueFigures
{
    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function execute(array $data): array
    {
        foreach (['totalrevenue', 'persenperubahanrevenue', 'datarevenueperbulan', 'datarevenueperminggu', 'total_revenue', 'persen_perubahan_revenue', 'totalSpentAll', 'total_spent', 'formatted_spent', 'avg_transaction', 'amount', 'jumlah', 'formatted_amount'] as $key) {
            unset($data[$key]);
        }
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->execute($value);
            }
        }

        return $data;
    }
}
