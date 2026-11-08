<?php

declare(strict_types=1);

namespace App\Http\Requests\Schedule;

use Illuminate\Foundation\Http\FormRequest;

class SaveOpeningHoursRequest extends FormRequest
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
            'days' => ['present', 'array'],
            'days.*' => ['array', 'max:6'],
            'days.*.*.opens_at' => ['required', 'date_format:H:i'],
            'days.*.*.closes_at' => ['required', 'date_format:H:i'],
            'slot_open_days_ahead' => ['nullable', 'integer', 'min:1', 'max:365'],
        ];
    }

    /**
     * @return array<int, list<array{opens_at: string, closes_at: string}>>
     */
    public function days(): array
    {
        $days = [];
        foreach ((array) $this->validated('days') as $day => $intervals) {
            if ((int) $day >= 1 && (int) $day <= 7) {
                $days[(int) $day] = array_values((array) $intervals);
            }
        }

        return $days;
    }
}
