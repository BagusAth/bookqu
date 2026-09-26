<?php

declare(strict_types=1);

namespace App\Http\Requests\Owner;

use App\Traits\ResolvesOwnerTenant;
use Illuminate\Foundation\Http\FormRequest;

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
        return [
            'jumlah' => ['required', 'numeric', 'min:10000'],
        ];
    }
}
