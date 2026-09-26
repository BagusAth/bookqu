<?php

declare(strict_types=1);

namespace App\Http\Requests\Schedule;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkCreateScheduleRequest extends FormRequest
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
        $tenant = $this->route('tenant') ?? $this->user()?->tenant;

        return [
            'jenisslot' => ['required', 'in:harian,rentang'],
            'idlayanan' => [
                'required',
                'integer',
                Rule::exists('services', 'id')->where(function ($query) use ($tenant) {
                    if ($tenant) {
                        $query->where('idtenant', $tenant->id);
                    }
                }),
            ],
            'tanggal'        => ['nullable', 'date'],
            'tanggalmulai'   => ['nullable', 'date'],
            'tanggalselesai' => ['nullable', 'date', 'after_or_equal:tanggalmulai'],
            'jammulai'       => ['required', 'date_format:H:i'],
            'jamselesai'     => ['required', 'date_format:H:i', 'after:jammulai'],
            'intervalslot'   => ['required', 'integer', 'min:5', 'max:480'],
        ];
    }
}
