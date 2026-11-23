<?php

namespace App\Actions\Owner;

use App\Models\User;
use App\Support\TenantActor;
use Illuminate\Support\Facades\DB;

final class UpdateNotificationPreferences
{
    /** @param array<string, bool> $preferences */
    public function execute(User $user, array $preferences): void
    {
        DB::transaction(function () use ($user, $preferences): void {
            $current = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $current->forceFill(['notification_preferences' => $preferences + ($current->notification_preferences ?? [])])->save();
            if ($current->wasChanged()) {
                app(TenantActor::class)->recordAccountChange($current->id);
            }
        });
    }
}
