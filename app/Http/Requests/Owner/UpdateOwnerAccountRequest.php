<?php

declare(strict_types=1);

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOwnerAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'namalengkap' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($userId)],
            'nomorhp' => ['required', 'string', 'max:20'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            // BQ-098: changing the email or the password needs proof of the current password.
            'current_password' => [Rule::requiredIf(fn (): bool => $this->changesCredentials()), 'nullable', 'string', 'current_password'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_password.required' => 'Masukkan password saat ini untuk mengganti email atau password.',
            'current_password.current_password' => 'Password saat ini salah.',
        ];
    }

    /**
     * Whether the request changes the sign-in email or sets a new password.
     */
    public function changesCredentials(): bool
    {
        $currentEmail = (string) ($this->user()->email ?? '');

        return $this->filled('password')
            || strcasecmp(trim((string) $this->input('email')), trim($currentEmail)) !== 0;
    }
}
