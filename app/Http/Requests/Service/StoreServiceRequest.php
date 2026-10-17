<?php

declare(strict_types=1);

namespace App\Http\Requests\Service;

use App\Traits\ResolvesOwnerTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceRequest extends FormRequest
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
        $tenant = $this->resolveTenant();
        $tenantId = $tenant?->id;

        return [
            'namalayanan'         => ['required', 'string', 'max:255'],
            'harga'               => ['required', 'numeric', 'min:0'],
            'durasi'              => ['required', 'integer', 'min:5', 'max:480'],
            'idcategory'          => ['nullable', Rule::exists('categories', 'id')->where('idtenant', $tenantId)],
            'deskripsi'           => ['nullable', 'string', 'max:1000'],
            'cover_image'         => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg,gif', 'max:10240'],
            'staff_ids'           => ['nullable', 'array'],
            'staff_ids.*'         => ['integer', Rule::exists('staff', 'id')->where('idtenant', $tenantId)],
            'resource_ids'        => ['nullable', 'array'],
            'resource_ids.*'      => ['integer', Rule::exists('resources', 'id')->where('idtenant', $tenantId)],
            'additional_item_ids' => ['nullable', 'array'],
            'additional_item_ids.*' => ['integer', Rule::exists('additional_items', 'id')->where('idtenant', $tenantId)],
        ];
    }
}
