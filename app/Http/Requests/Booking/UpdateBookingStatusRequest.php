<?php

namespace App\Http\Requests\Booking;

use App\Domain\Booking\BookingState;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBookingStatusRequest extends FormRequest
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
            'status' => [
                'required',
                'string',
                'in:' . implode(',', [
                    BookingState::STATUS_PAID,
                    BookingState::STATUS_COMPLETED,
                    BookingState::STATUS_CANCELLED,
                ]),
            ],
        ];
    }
}
