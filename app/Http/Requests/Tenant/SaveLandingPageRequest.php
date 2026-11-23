<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Actions\Tenant\SaveLandingPage;
use App\Models\TenantLandingPage;
use App\Traits\ResolvesOwnerTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Halaman Booking → Landing page (BQ-154, FR-PUBLIC-007): a known template and sections, a short
 * headline, and a banner from this business's gallery only. Pro is enforced by the route.
 */
class SaveLandingPageRequest extends FormRequest
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
            'template' => ['required', 'string', Rule::in(array_keys(TenantLandingPage::TEMPLATES))],
            'headline' => ['nullable', 'string', 'max:120'],
            'sections' => ['nullable', 'array'],
            'sections.*' => ['string', 'distinct', Rule::in(array_keys(TenantLandingPage::SECTIONS))],
            'banner_asset_id' => ['nullable', 'integer', Rule::exists('assets', 'id')->where('idtenant', $this->resolveTenant()?->id)],
            'action' => ['required', 'string', Rule::in(SaveLandingPage::ACTIONS)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'template.in' => 'Pilih salah satu template yang tersedia.',
            'sections.*.in' => 'Pilih bagian dari daftar yang tersedia.',
            'banner_asset_id.exists' => 'Pilih banner dari galeri bisnis Anda.',
            'headline.max' => 'Judul paling banyak 120 karakter.',
        ];
    }
}
