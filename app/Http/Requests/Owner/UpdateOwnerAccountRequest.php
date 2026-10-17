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
            'email'       => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($userId)],
            'nomorhp'     => ['required', 'string', 'max:20'],
            'password'    => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }
}
