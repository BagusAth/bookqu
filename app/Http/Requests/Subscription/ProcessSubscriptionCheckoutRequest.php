<?php

declare(strict_types=1);

namespace App\Http\Requests\Subscription;

use App\Traits\ResolvesOwnerTenant;
use Illuminate\Foundation\Http\FormRequest;

class ProcessSubscriptionCheckoutRequest extends FormRequest
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
            'plan_id'        => ['required', 'exists:plans,id'],
            'nama_pembayar'  => ['required', 'string', 'max:100'],
            'email_pembayar' => ['required', 'email', 'max:100'],
            'hp_pembayar'    => ['required', 'string', 'max:20'],
            'catatan'        => ['nullable', 'string', 'max:500'],
        ];
    }
}
