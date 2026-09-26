<?php

declare(strict_types=1);

namespace App\Http\Requests\Customer;

use App\Traits\ResolvesOwnerTenant;
use Illuminate\Foundation\Http\FormRequest;

class SaveCustomerNoteRequest extends FormRequest
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
            'customer_identifier' => ['required', 'string', 'max:190'],
            'notes'               => ['nullable', 'string', 'max:2000'],
        ];
    }
}
