<?php

declare(strict_types=1);

namespace App\Actions\EarlyAccess;

use App\Models\EarlyAccessSignup;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

/**
 * Stores an early access sign-up (BQ-175, FR-PUBLIC-009). The input is already validated and
 * normalized; the unique email index is the final guard when two submissions race.
 */
class CreateEarlyAccessSignup
{
    /**
     * @param  array{full_name: string, business_name: string, business_type: string, email: string, whatsapp_number: string}  $data
     *
     * @throws ValidationException when the email signed up in the meantime
     */
    public function execute(array $data): EarlyAccessSignup
    {
        try {
            return EarlyAccessSignup::create([
                'full_name' => $data['full_name'],
                'business_name' => $data['business_name'],
                'business_type' => $data['business_type'],
                'email' => $data['email'],
                'whatsapp_number' => $data['whatsapp_number'],
            ]);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'email' => 'Email ini sudah terdaftar early access. Kami akan menghubungi kamu segera.',
            ]);
        }
    }
}
