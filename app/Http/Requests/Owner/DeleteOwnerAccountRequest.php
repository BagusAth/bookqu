<?php

declare(strict_types=1);

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;

class DeleteOwnerAccountRequest extends FormRequest
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
        return [
            'confirm_account' => ['nullable', 'string', 'max:255'],
            // BQ-098: deleting the account and business needs the current password, not only on-screen text.
            'delete_password' => ['required', 'string', 'current_password'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'delete_password.required' => 'Masukkan password Anda untuk menghapus akun.',
            'delete_password.current_password' => 'Password salah.',
        ];
    }
}
