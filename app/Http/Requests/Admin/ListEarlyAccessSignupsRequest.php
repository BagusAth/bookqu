<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Search of the admin early access list (BQ-175, FR-ADMIN-006). Access is decided by `role:admin`.
 */
class ListEarlyAccessSignupsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function search(): string
    {
        return trim((string) $this->validated('q', ''));
    }
}
