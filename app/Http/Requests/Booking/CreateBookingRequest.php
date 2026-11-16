<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            'namapelanggan' => ['required', 'string', 'min:2', 'max:150'],
            // 10–15 digits, optionally "+" first and single spaces or dashes between digits
            'nomorhp' => ['required', 'string', 'max:20', 'regex:/^\+?(?:\d[\s\-]?){9,14}\d$/'],
            'email' => ['required', 'email', 'max:100'],
            'catatan' => ['nullable', 'string', 'max:500'],
            // Room for the participants is decided by the claim (FR-BOOKING-021)
            'peserta' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'voucher_code' => ['nullable', 'string', 'max:50'],
            'checkout_fingerprint' => ['nullable', 'string', 'max:128'],
        ] + QuoteBookingRequest::addOnRules();
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [
            static function (Validator $validator): void {
                if ($validator->errors()->has('selected_addons.*')) {
                    $validator->errors()->add('selected_addons', 'Pilihan add-on tidak valid.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nomorhp.regex' => 'Nomor WhatsApp / HP harus berisi 10–15 digit angka (boleh diawali +62).',
        ];
    }
}
