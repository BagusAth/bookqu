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
            'namalayanan' => ['required', 'string', 'max:255'],
            'harga' => ['required', 'numeric', 'min:0'],
            'durasi' => ['required', 'integer', 'min:5', 'max:480', 'multiple_of:5'],
            'idcategory' => ['nullable', Rule::exists('categories', 'id')->where('idtenant', $tenantId)],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'satuan_harga' => ['nullable', 'string', 'max:20'],
            'satuan_durasi' => ['nullable', 'string', 'max:20'],
            'is_popular' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            // BQ-142 (FR-ASSET-005): an image of this business's gallery, or a new upload into it.
            'image_asset_id' => ['nullable', 'integer', Rule::exists('assets', 'id')->where('idtenant', $tenantId)],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:10240'],
            'resource_ids' => ['nullable', 'array'],
            'resource_ids.*' => ['integer', Rule::exists('resources', 'id')->where('idtenant', $tenantId)],
            'additional_item_ids' => ['nullable', 'array'],
            'additional_item_ids.*' => ['integer', Rule::exists('additional_items', 'id')->where('idtenant', $tenantId)],
        ];
    }
}
