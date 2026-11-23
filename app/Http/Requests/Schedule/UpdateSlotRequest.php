<?php

declare(strict_types=1);

namespace App\Http\Requests\Schedule;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateSlotRequest extends FormRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'operation' => ['required', 'in:open,close,price'],
            'harga_override' => ['present_if:operation,price', 'nullable', 'integer', 'between:0,9999999999'],
        ];
    }
}
