<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Support\Arr;

/**
 * Business data for an owner who registers through Google (D-029): the registration fields
 * without email and password, which come from Google.
 */
class CompleteGoogleRegistrationRequest extends RegisterRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return Arr::except(parent::rules(), ['email', 'password']);
    }
}
