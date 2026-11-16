<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Domain\Tenant\CustomDomain;
use App\Models\Tenant;
use App\Traits\ResolvesOwnerTenant;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Halaman Booking → Domain sendiri (BQ-154, D-005): the domain is normalised before validation,
 * must be a real host name outside the platform, and may belong to one business only (AC-HBK-04).
 */
class UpdateCustomDomainRequest extends FormRequest
{
    use ResolvesOwnerTenant;

    public function authorize(): bool
    {
        return $this->resolveTenant() !== null;
    }

    protected function prepareForValidation(): void
    {
        $raw = $this->input('custom_domain');
        if (! is_string($raw) || trim($raw) === '') {
            $this->merge(['custom_domain' => null]);

            return;
        }

        $normalized = CustomDomain::normalize($raw);
        $this->merge(['custom_domain' => $normalized !== '' ? $normalized : trim($raw)]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = $this->resolveTenant()?->id;

        return [
            'custom_domain' => [
                'nullable', 'string', 'max:253',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value) || ! CustomDomain::isAllowed($value)) {
                        $fail('Masukkan nama domain yang valid, misalnya booking.namabisnis.com.');
                    }
                },
                function (string $attribute, mixed $value, Closure $fail) use ($tenantId): void {
                    $taken = is_string($value) && Tenant::query()
                        ->whereRaw('LOWER(custom_domain) = ?', [$value])
                        ->whereKeyNot($tenantId)
                        ->exists();
                    if ($taken) {
                        $fail('Domain ini sudah dipakai bisnis lain.');
                    }
                },
            ],
        ];
    }
}
