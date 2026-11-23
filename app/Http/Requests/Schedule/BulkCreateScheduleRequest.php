<?php

declare(strict_types=1);

namespace App\Http\Requests\Schedule;

use App\Actions\Schedule\BulkCreateSchedules;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'tanggal' => ['nullable', 'required_if:jenisslot,harian', 'date', 'after_or_equal:today'],
            'tanggalmulai' => ['nullable', 'required_if:jenisslot,rentang', 'date', 'after_or_equal:today'],
            'tanggalselesai' => ['nullable', 'required_if:jenisslot,rentang', 'date', 'after_or_equal:tanggalmulai'],
            'jammulai' => ['required', 'date_format:H:i'],
            'jamselesai' => ['required', 'date_format:H:i', 'after:jammulai'],
            'intervalslot' => ['required', 'integer', 'min:5', 'max:480', 'multiple_of:5'],
        ];
    }

    /**
     * Cap a `rentang` request to BulkCreateSchedules::MAX_RANGE_DAYS after the start date (BQ-107).
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->input('jenisslot') !== 'rentang'
                    || $validator->errors()->hasAny(['tanggalmulai', 'tanggalselesai'])) {
                    return;
                }

                $start = Carbon::parse((string) $this->input('tanggalmulai'))->startOfDay();
                $end = Carbon::parse((string) $this->input('tanggalselesai'))->startOfDay();

                if ($end->greaterThan($start->copy()->addDays(BulkCreateSchedules::MAX_RANGE_DAYS))) {
                    $validator->errors()->add(
                        'tanggalselesai',
                        'Rentang tanggal maksimal '.BulkCreateSchedules::MAX_RANGE_DAYS.' hari dari tanggal mulai.'
                    );
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tanggal.after_or_equal' => 'Tanggal slot tidak boleh di masa lalu.',
            'tanggalmulai.after_or_equal' => 'Tanggal mulai tidak boleh di masa lalu.',
        ];
    }
}
