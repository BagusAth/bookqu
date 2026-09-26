<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Traits\ResolvesOwnerTenant;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentSettingsRequest extends FormRequest
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
            'payment_mode'                 => ['required', 'in:platform,owner'],
            'midtrans_environment'         => ['required', 'in:sandbox,production'],
            'midtrans_sandbox_merchant_id' => ['nullable', 'string', 'max:100'],
            'midtrans_sandbox_client_key'  => ['nullable', 'string', 'max:200'],
            'midtrans_sandbox_server_key'  => ['nullable', 'string', 'max:200'],
            'midtrans_prod_merchant_id'    => ['nullable', 'string', 'max:100'],
            'midtrans_prod_client_key'     => ['nullable', 'string', 'max:200'],
            'midtrans_prod_server_key'     => ['nullable', 'string', 'max:200'],
        ];
    }
}
