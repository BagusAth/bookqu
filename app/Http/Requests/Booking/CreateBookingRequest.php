<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class CreateBookingRequest extends FormRequest
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
            'namapelanggan'   => ['required', 'string', 'max:150'],
            'nomorhp'         => ['required', 'string', 'max:20'],
            'email'           => ['required', 'email', 'max:100'],
            'catatan'         => ['nullable', 'string', 'max:500'],
            'selected_addons' => ['nullable'],
            'voucher_code'    => ['nullable'],
        ];
    }

    /**
     * Configure the validator instance with business guards for disabled production features.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Guard: Add-on feature disabled in production
            if ($this->filled('selected_addons') || (!empty($this->selected_addons))) {
                $validator->errors()->add('selected_addons', 'Fitur item tambahan (add-on) belum tersedia.');
                $validator->errors()->add('addons', 'Fitur item tambahan (add-on) belum tersedia.');
            }

            // Guard: Voucher feature disabled in production
            if ($this->filled('voucher_code')) {
                $validator->errors()->add('voucher_code', 'Fitur voucher diskon belum tersedia.');
                $validator->errors()->add('voucher', 'Fitur voucher diskon belum tersedia.');
            }
        });
    }
}
