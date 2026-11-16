<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class QuoteBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'voucher_code' => ['nullable', 'string', 'max:50'],
            'peserta' => ['nullable', 'integer', 'min:1', 'max:9999'],
        ] + self::addOnRules();
    }

    /** @return array<string, list<string>> */
    public static function addOnRules(): array
    {
        return [
            'selected_addons' => ['nullable', 'array', 'list', 'max:50'],
            'selected_addons.*' => ['required', 'array:id,quantity'],
            'selected_addons.*.id' => ['required', 'integer', 'min:1', 'distinct'],
            'selected_addons.*.quantity' => ['required', 'integer', 'between:1,9999'],
        ];
    }
}
