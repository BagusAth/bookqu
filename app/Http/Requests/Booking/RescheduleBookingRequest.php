<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class RescheduleBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'schedule_id' => ['required', 'integer'],
            'tanggal'     => ['nullable', 'date', 'after_or_equal:today'],
            'alasan'      => ['nullable', 'string', 'max:255'],
        ];
    }
}
