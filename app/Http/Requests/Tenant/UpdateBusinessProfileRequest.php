<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Traits\ResolvesOwnerTenant;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBusinessProfileRequest extends FormRequest
{
    use ResolvesOwnerTenant;

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
            'namabisnis'  => ['required', 'string', 'max:150'],
            'jenisbisnis' => ['required', 'string', 'max:150'],
            'nomorhp'     => ['required', 'string', 'max:20'],
            'alamat'      => ['required', 'string', 'max:255'],
            'deskripsi'   => ['nullable', 'string', 'max:1000'],
            'logo'        => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg,gif', 'max:10240'],
        ];
    }
}
