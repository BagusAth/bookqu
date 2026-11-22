<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ResolveGoogleSignIn
{
    /**
     * Find the user a Google account signs in as (FR-AUTH-009, D-029). The first sign-in links an
     * existing account with the same email, but only when Google verified that email. Admin
     * accounts never use Google. Returns null when no account exists yet (registration).
     *
     * @throws GoogleSignInRejected
     */
    public function execute(string $googleId, string $email, bool $emailVerified): ?User
    {
        if ($googleId === '' || $email === '' || ! $emailVerified) {
            throw new GoogleSignInRejected('Google account without a verified email.');
        }

        $user = User::query()->where('google_id', $googleId)->first();

        if ($user === null) {
            $user = User::query()->where('email', $email)->first();

            if ($user === null) {
                return null;
            }

            if ($user->google_id !== null) {
                throw new GoogleSignInRejected('Email is linked to another Google account.');
            }
        }

        if ($user->isAdmin()) {
            throw new GoogleSignInRejected('Admin accounts cannot use Google sign-in.');
        }

        if ($user->google_id === null && $user->email_verified_at === null) {
            $this->takeOverUnverifiedAccount($user);
        }

        $user->forceFill([
            'google_id' => $googleId,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        return $user;
    }

    /**
     * Whoever created an account with an unverified email never proved they own it. The verified
     * Google owner takes it over, so the earlier password, "remember me" and sessions stop working
     * (pre-account takeover). The owner sets a new password through FR-AUTH-007 if wanted.
     */
    private function takeOverUnverifiedAccount(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->forceFill(['password' => null])->setRememberToken(Str::random(60));
            $user->save();

            if (config('session.driver') === 'database') {
                DB::connection(config('session.connection'))
                    ->table((string) config('session.table', 'sessions'))
                    ->where('user_id', $user->id)
                    ->delete();
            }
        });
    }
}
