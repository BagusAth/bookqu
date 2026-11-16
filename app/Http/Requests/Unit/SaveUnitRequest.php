<?php

declare(strict_types=1);

namespace App\Http\Requests\Unit;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Unit fields of owner-portal §8.7; service ownership is checked by SaveUnit. */
class SaveUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'string', 'max:100'],
            'kapasitas' => ['required', 'integer', 'min:1', 'max:9999'],
            'image_asset_id' => ['sometimes', 'nullable', 'integer', Rule::exists('assets', 'id')->where('idtenant', app(TenantContext::class)->getTenantId())],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'service_ids' => ['nullable', 'array'],
            'service_ids.*' => ['integer'],
        ];
    }
}
