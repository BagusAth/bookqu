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
            'namapelanggan' => ['required', 'string', 'min:2', 'max:150'],
            // 10–15 digits, optionally "+" first and single spaces or dashes between digits
            'nomorhp' => ['required', 'string', 'max:20', 'regex:/^\+?(?:\d[\s\-]?){9,14}\d$/'],
            'email' => ['required', 'email', 'max:100'],
            'catatan' => ['nullable', 'string', 'max:500'],
            'selected_addons' => ['nullable'],
            'voucher_code' => ['nullable'],
            'checkout_fingerprint' => ['nullable', 'string', 'max:128'],
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

    /**
     * Configure the validator instance with business guards for disabled production features.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Guard: Add-on feature disabled in production
            if ($this->filled('selected_addons') || (! empty($this->selected_addons))) {
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
