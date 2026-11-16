<?php

declare(strict_types=1);

namespace App\Http\Requests\Unit;

use Illuminate\Foundation\Http\FormRequest;

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
            'lokasi' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'service_ids' => ['nullable', 'array'],
            'service_ids.*' => ['integer'],
        ];
    }
}
