<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Actions\Media\StoreMediaAsset;
use App\Traits\ResolvesOwnerTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Upload into the media gallery (BQ-142, FR-ASSET-005): an image of at most 10 MB. SVG is refused
 * because it can carry script.
 */
class StoreMediaAssetRequest extends FormRequest
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
            'file' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:'.StoreMediaAsset::MAX_KILOBYTES],
            'title' => ['nullable', 'string', 'max:150'],
            'category' => ['nullable', 'string', Rule::in(array_keys(StoreMediaAsset::CATEGORIES))],
        ];
    }
}
