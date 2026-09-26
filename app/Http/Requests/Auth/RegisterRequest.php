<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:100'],
            'email'        => ['required', 'email', 'max:100', 'unique:users,email'],
            'nomorhp'      => ['required', 'string', 'max:20'],
            'nama_bisnis'  => ['required', 'string', 'max:150'],
            'jenis_bisnis' => ['required', 'string', 'max:150'],
            'alamat'       => ['required', 'string', 'max:255'],
            'password'     => ['required', 'confirmed', Password::min(8)],
            'terms'        => ['accepted'],
        ];
    }
}
