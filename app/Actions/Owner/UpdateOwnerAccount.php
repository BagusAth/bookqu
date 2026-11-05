<?php

declare(strict_types=1);

namespace App\Actions\Owner;

use App\Models\User;
use App\Notifications\AccountEmailChangedNotification;
use App\Support\TenantActor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Throwable;

class UpdateOwnerAccount
{
    /**
     * Update user credentials and profile details.
     *
     * The caller has already verified the current password for an email or password change
     * (BQ-098). A new email loses its verification and both addresses are told; a new
     * password ends every other session of the user.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(User $user, array $data, ?string $currentSessionId = null): User
    {
        $previousEmail = (string) $user->email;
        $emailChanged = strcasecmp(trim((string) $data['email']), trim($previousEmail)) !== 0;
        $passwordChanged = ! empty($data['password']);

        $user->namalengkap = $data['namalengkap'];
        $user->email = $data['email'];
        $user->nomorhp = $data['nomorhp'];

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        if ($passwordChanged) {
            $user->password = Hash::make($data['password']);
            $user->setRememberToken(Str::random(60));
        }

        DB::transaction(function () use ($user, $passwordChanged, $currentSessionId): void {
            $user->save();

            if ($passwordChanged) {
                $this->endOtherSessions($user, $currentSessionId);
            }
        });

        if ($user->wasChanged()) {
            app(TenantActor::class)->recordAccountChange($user->id);
        }

        if ($emailChanged) {
            $this->notifyEmailChange($user, $previousEmail);
        }

        return $user;
    }

    /**
     * Delete the user's other stored sessions. Production keeps sessions in the database
     * (`docs/operations.md`); the rotated remember token also ends "remember me" logins.
     */
    private function endOtherSessions(User $user, ?string $currentSessionId): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::connection(config('session.connection'))
            ->table((string) config('session.table', 'sessions'))
            ->where('user_id', $user->id)
            ->when($currentSessionId !== null, fn ($query) => $query->where('id', '!=', $currentSessionId))
            ->delete();
    }

    private function notifyEmailChange(User $user, string $previousEmail): void
    {
        try {
            $user->sendEmailVerificationNotification();
            Notification::route('mail', $previousEmail)
                ->notify(new AccountEmailChangedNotification(self::maskEmail((string) $user->email)));
        } catch (Throwable $exception) {
            Log::warning('UpdateOwnerAccount: email change notification failed', [
                'user_id' => $user->id,
                'exception' => $exception::class,
            ]);
        }
    }

    private static function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return mb_substr($local, 0, 1).'***@'.$domain;
    }
}
