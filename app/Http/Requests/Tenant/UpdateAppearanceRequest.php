<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Traits\ResolvesOwnerTenant;
use Illuminate\Foundation\Http\FormRequest;

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
        return [
            'theme_color'  => ['nullable', 'string', 'max:20'],
            'button_style' => ['nullable', 'string', 'max:50'],
            'font_family'  => ['nullable', 'string', 'max:100'],
            'card_style'   => ['nullable', 'string', 'max:50'],
            'deskripsi'    => ['nullable', 'string', 'max:1000'],
        ];
    }
}
