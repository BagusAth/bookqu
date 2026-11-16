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
            'current_password' => [Rule::requiredIf(fn (): bool => $this->changesAccount()), 'nullable', 'string', 'current_password'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_password.required' => 'Masukkan kata sandi saat ini untuk menyimpan perubahan akun.',
            'current_password.current_password' => 'Password saat ini salah.',
        ];
    }

    private function changesAccount(): bool
    {
        $user = $this->user();
        $currentEmail = (string) ($user->email ?? '');

        return $this->filled('password')
            || (string) $this->input('namalengkap') !== (string) $user?->namalengkap
            || (string) $this->input('nomorhp') !== (string) $user?->nomorhp
            || strcasecmp(trim((string) $this->input('email')), trim($currentEmail)) !== 0;
    }
}
