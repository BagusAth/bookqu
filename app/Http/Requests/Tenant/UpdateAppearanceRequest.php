<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Support\StorefrontBranding;
use App\Traits\ResolvesOwnerTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Halaman Booking → Tampilan (BQ-142, owner-portal.md §8.9). Values reach the storefront's style
 * block, so each one is an exact allowed value; images come from this business's gallery only.
 */
class UpdateAppearanceRequest extends FormRequest
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
        $ownAsset = Rule::exists('assets', 'id')->where('idtenant', $this->resolveTenant()?->id);

        return [
            'theme_color' => ['nullable', 'string', 'regex:'.StorefrontBranding::COLOR_PATTERN],
            'button_style' => ['nullable', 'string', Rule::in(array_keys(StorefrontBranding::BUTTON_STYLES))],
            'card_style' => ['nullable', 'string', Rule::in(array_keys(StorefrontBranding::CARD_STYLES))],
            'font_family' => ['nullable', 'string', Rule::in(StorefrontBranding::FONTS)],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'logo_asset_id' => ['nullable', 'integer', $ownAsset],
            'cover_asset_id' => ['nullable', 'integer', $ownAsset],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'theme_color.regex' => 'Warna tema harus kode heksadesimal, misalnya #4F46E5.',
            'logo_asset_id.exists' => 'Pilih logo dari galeri bisnis Anda.',
            'cover_asset_id.exists' => 'Pilih sampul dari galeri bisnis Anda.',
        ];
    }
}
