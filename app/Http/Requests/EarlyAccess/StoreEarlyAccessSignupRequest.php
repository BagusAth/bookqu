<?php

declare(strict_types=1);

namespace App\Http\Requests\EarlyAccess;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Early access form on `/early-access` (BQ-175, FR-PUBLIC-009). Public, so every field is untrusted.
 */
class StoreEarlyAccessSignupRequest extends FormRequest
{
    /** Name of the hidden field that people never fill in and simple bots do. */
    public const HONEYPOT = 'website';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Trim text, lowercase the email and bring every Indonesian WhatsApp notation to `08…`.
     */
    protected function prepareForValidation(): void
    {
        $text = fn (string $key): ?string => is_string($this->input($key))
            ? preg_replace('/\s+/u', ' ', trim($this->input($key)))
            : $this->input($key);

        $this->merge([
            'full_name' => $text('full_name'),
            'business_name' => $text('business_name'),
            'business_type' => $text('business_type'),
            'email' => is_string($this->input('email')) ? mb_strtolower(trim($this->input('email'))) : $this->input('email'),
            'whatsapp_number' => $this->normalizedWhatsapp(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'min:3', 'max:100', "regex:/^[\pL][\pL\s.'-]*$/u"],
            'business_name' => ['required', 'string', 'min:2', 'max:150'],
            'business_type' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'string', 'email:rfc', 'max:150', Rule::unique('early_access_signups', 'email')],
            // Indonesian mobile numbers: 08 + operator digit 1–9, 10–13 digits in total
            'whatsapp_number' => ['required', 'string', 'regex:/^08[1-9]\d{7,10}$/'],
            self::HONEYPOT => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'full_name.regex' => 'Nama lengkap hanya boleh berisi huruf, spasi, titik, apostrof, dan tanda hubung.',
            'email.email' => 'Format email belum benar, contoh: nama@bisnis.com.',
            'email.unique' => 'Email ini sudah terdaftar early access. Kami akan menghubungi kamu segera.',
            'whatsapp_number.regex' => 'Nomor WhatsApp harus nomor HP Indonesia, contoh: 0812 3456 7890.',
            self::HONEYPOT.'.prohibited' => 'Pendaftaran tidak dapat diproses. Muat ulang halaman lalu coba lagi.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'full_name' => 'nama lengkap',
            'business_name' => 'nama bisnis',
            'business_type' => 'jenis bisnis',
            'email' => 'email',
            'whatsapp_number' => 'nomor WhatsApp',
        ];
    }

    /**
     * Return to the form section, so the visitor sees the errors next to their input.
     */
    protected function getRedirectUrl(): string
    {
        return route('early-access').'#daftar';
    }

    private function normalizedWhatsapp(): mixed
    {
        $value = $this->input('whatsapp_number');
        if (! is_string($value)) {
            return $value;
        }

        $digits = preg_replace('/[\s\-().]/', '', trim($value));
        if (str_starts_with($digits, '+62')) {
            return '0'.substr($digits, 3);
        }
        if (str_starts_with($digits, '62')) {
            return '0'.substr($digits, 2);
        }
        if (str_starts_with($digits, '8')) {
            return '0'.$digits;
        }

        return $digits;
    }
}
