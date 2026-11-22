<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Steps after a successful sign-in, shared by password and Google sign-in (BQ-172).
 */
trait FinishesSignIn
{
    protected function finishSignIn(Request $request): RedirectResponse
    {
        $request->session()->regenerate();
        $request->session()->forget('current_tenant_id');

        $user = $request->user();

        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        if ($user?->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        if ($user?->isOwner()) {
            return redirect()->intended(route('owner.dashboard'));
        }

        return redirect()->intended('/');
    }
}
