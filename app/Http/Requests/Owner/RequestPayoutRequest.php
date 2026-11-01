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
            'jumlah' => ['required', 'numeric', 'min:10000', 'decimal:0,2'],
            'bank_nama' => ['required', 'string', 'max:100'],
            'nomor_rekening' => ['required', 'string', 'regex:/^[0-9]{5,30}$/'],
            'nama_pemilik_rekening' => ['required', 'string', 'max:150'],
        ];
    }
}
