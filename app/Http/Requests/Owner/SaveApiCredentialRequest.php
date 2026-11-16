<?php

declare(strict_types=1);

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;

final class SaveApiCredentialRequest extends FormRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'url' => [$this->routeIs('owner.api.webhooks.store') ? 'required' : 'nullable', 'url:https', 'max:2048'],
        ];
    }
}
