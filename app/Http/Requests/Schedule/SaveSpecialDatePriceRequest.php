<?php

declare(strict_types=1);

namespace App\Http\Requests\Schedule;

use Illuminate\Foundation\Http\FormRequest;

class SaveSpecialDatePriceRequest extends FormRequest
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
            'tanggal' => ['required', 'date', 'after_or_equal:today'],
            'price' => ['required', 'numeric', 'min:0', 'max:100000000'],
            'service_ids' => ['required', 'array', 'min:1'],
            'service_ids.*' => ['integer'],
        ];
    }
}
