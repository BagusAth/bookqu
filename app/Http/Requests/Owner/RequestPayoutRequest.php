<?php

declare(strict_types=1);

namespace App\Http\Requests\Owner;

use App\Traits\ResolvesOwnerTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RequestPayoutRequest extends FormRequest
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
        $tenantId = $this->resolveTenant()?->id;

        return [
            'request_key' => ['required', 'uuid'],
            'payout_account_id' => [
                'required',
                'integer',
                Rule::exists('tenant_payout_accounts', 'id')
                    ->where('idtenant', $tenantId)
                    ->whereNull('deleted_at'),
            ],
            'jumlah' => ['required', 'integer', 'min:10000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'request_key.required' => 'Kunci pengajuan tidak valid.',
            'request_key.uuid' => 'Kunci pengajuan harus berupa UUID.',
            'payout_account_id.required' => 'Pilih rekening penarikan tujuan.',
            'payout_account_id.exists' => 'Rekening penarikan yang dipilih tidak valid.',
            'jumlah.required' => 'Nominal withdraw wajib diisi.',
            'jumlah.integer' => 'Nominal withdraw harus berupa angka bulat.',
            'jumlah.min' => 'Nominal withdraw minimal Rp 10.000.',
        ];
    }
}
