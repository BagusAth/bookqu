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
        return [
            'tanggal_block'       => ['nullable', 'date'],
            'alasan'              => ['nullable', 'string', 'max:200'],
            'weekend_price_type'  => ['required', 'in:none,multiplier,fixed'],
            'weekend_price_value' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
