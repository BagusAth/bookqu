<?php

declare(strict_types=1);

namespace App\Http\Requests\Schedule;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $dates = [
            'tanggal_block' => ['nullable', 'date'],
            'alasan' => ['nullable', 'string', 'max:200'],
        ];
        $pricing = [
            'weekend_price_type' => ['required', 'in:none,multiplier,fixed'],
            'weekend_price_value' => ['nullable', 'numeric', 'min:0'],
        ];

        return match ($this->query('tab')) {
            'closed' => array_replace($dates, ['tanggal_block' => ['required', 'date']]),
            'prices' => $pricing,
            default => $dates + $pricing,
        };
    }
}
