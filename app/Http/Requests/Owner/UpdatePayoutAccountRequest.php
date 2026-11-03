<?php

declare(strict_types=1);

namespace App\Http\Requests\Owner;

use App\Traits\ResolvesOwnerTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePayoutAccountRequest extends FormRequest
{
    use ResolvesOwnerTenant;

    public function authorize(): bool
    {
        return $this->resolveTenant() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'bank_kode' => ['required', 'string', Rule::in(array_keys((array) config('banks')))],
            'nomor_rekening' => ['required', 'string', 'regex:/^[0-9]{5,30}$/'],
            'nama_pemilik_rekening' => ['required', 'string', 'max:150'],
            'current_password' => ['required', 'current_password'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'bank_kode.in' => 'Bank yang dipilih tidak valid.',
            'nomor_rekening.regex' => 'Nomor rekening harus berupa angka 5 hingga 30 digit.',
            'current_password.current_password' => 'Password saat ini tidak sesuai.',
            'current_password.required' => 'Password saat ini wajib diisi untuk verifikasi keamanan.',
        ];
    }
}
