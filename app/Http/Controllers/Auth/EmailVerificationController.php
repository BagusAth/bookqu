<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    /**
     * Display the email verification prompt.
     */
    public function notice(Request $request): RedirectResponse|View
    {
        if ($request->user()?->hasVerifiedEmail()) {
            return $request->user()->isOwner()
                ? redirect()->route('owner.dashboard')
                : redirect('/');
        }

        return view('auth.verify-email');
    }

    /**
     * Mark the user's email address as verified.
     */
    public function verify(Request $request, string $id, string $hash): RedirectResponse
    {
        $user = User::findOrFail($id);

        if (! hash_equals(sha1($user->getEmailForVerification()), (string) $hash)) {
            abort(403, 'Link verifikasi tidak valid.');
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        if (Auth::check() && Auth::id() !== $user->id) {
            Auth::logout();
        }

        return redirect()->route('login')->with('status', 'Akun berhasil diaktivasi. Silakan login.');
    }

    /**
     * Send a new email verification notification.
     */
    public function send(Request $request): RedirectResponse
    {
        if ($request->user()?->hasVerifiedEmail()) {
            return $request->user()->isOwner()
                ? redirect()->route('owner.dashboard')
                : redirect('/');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'Link verifikasi baru sudah dikirim ke email Anda.');
    }
}
