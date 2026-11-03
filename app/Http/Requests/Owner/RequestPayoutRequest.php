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
            'jumlah' => ['required', 'numeric', 'min:10000', 'decimal:0,2'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payout_account_id.required' => 'Pilih rekening penarikan tujuan.',
            'payout_account_id.exists' => 'Rekening penarikan yang dipilih tidak valid.',
        ];
    }
}
