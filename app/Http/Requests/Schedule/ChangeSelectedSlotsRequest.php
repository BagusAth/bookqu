<?php

declare(strict_types=1);

namespace App\Http\Requests\Schedule;

use Illuminate\Foundation\Http\FormRequest;

final class ChangeSelectedSlotsRequest extends FormRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'operation' => ['required', 'in:close,delete'],
            'slot_ids' => ['required', 'array', 'list', 'min:1', 'max:100'],
            'slot_ids.*' => ['required', 'integer', 'distinct'],
        ];
    }
}
