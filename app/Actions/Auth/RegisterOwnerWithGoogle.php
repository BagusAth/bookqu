<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Actions\Owner\RegisterOwner;
use App\Models\Tenant;
use App\Models\User;

class RegisterOwnerWithGoogle
{
    public function __construct(private RegisterOwner $registerOwner) {}

    /**
     * Register a new owner from a verified Google account (D-029): same tenant and trial as
     * FR-AUTH-001, no password, email already verified.
     *
     * @param  array{google_id: string, email: string}  $google
     * @param  array{name: string, nomorhp: string, nama_bisnis: string, jenis_bisnis: string, alamat: string}  $data
     * @return array{user: User, tenant: Tenant}
     *
     * @throws GoogleSignInRejected
     */
    public function execute(array $google, array $data): array
    {
        $taken = User::query()
            ->where('email', $google['email'])
            ->orWhere('google_id', $google['google_id'])
            ->exists();

        if ($taken) {
            throw new GoogleSignInRejected('Account was created meanwhile.');
        }

        return $this->registerOwner->execute([
            ...$data,
            'email' => $google['email'],
            'google_id' => $google['google_id'],
        ]);
    }
}
