<?php

declare(strict_types=1);

namespace App\Http\Requests\Category;

use App\Support\CategoryColor;
use App\Traits\ResolvesOwnerTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:100', Rule::unique('categories', 'name')->where('idtenant', $tenantId)->ignore((int) $this->route('id'))],
            'description' => ['nullable', 'string', 'max:500'],
            'color' => ['nullable', 'string', Rule::in(CategoryColor::keys())],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_image' => ['nullable', 'in:0,1'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'Kategori dengan nama ini sudah ada.',
        ];
    }
}
