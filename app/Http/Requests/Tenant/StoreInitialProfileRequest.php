<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class StoreInitialProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('nomorhp') && $this->nomorhp !== null) {
            $phone = preg_replace('/[^\d+]/', '', (string) $this->nomorhp);
            if (str_starts_with($phone, '+62')) {
                $phone = substr($phone, 3);
            } elseif (str_starts_with($phone, '62')) {
                $phone = substr($phone, 2);
            }
            $phone = ltrim($phone, '0');
            if ($phone !== '') {
                $phone = '0' . $phone;
            }
            $this->merge(['nomorhp' => $phone]);
        }
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
        ];
    }
}
