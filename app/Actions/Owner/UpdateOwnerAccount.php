<?php

declare(strict_types=1);

namespace App\Actions\Owner;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UpdateOwnerAccount
{
    /**
     * Update user credentials and profile details.
     *
     * @param array<string, mixed> $data
     */
    public function execute(User $user, array $data): User
    {
        $user->namalengkap = $data['namalengkap'];
        $user->email       = $data['email'];
        $user->nomorhp     = $data['nomorhp'];

        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return $user;
    }
}
