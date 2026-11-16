<?php

declare(strict_types=1);

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;

final class SaveTrackingSettingsRequest extends FormRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'google_analytics_id' => ['nullable', 'string', 'max:24', 'regex:/^G-[A-Z0-9]+$/D'],
            'meta_pixel_id' => ['nullable', 'string', 'regex:/^[0-9]{8,24}$/D'],
        ];
    }
}
