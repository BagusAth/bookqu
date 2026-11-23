<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;

/**
 * An account created through Google has no password (D-029). The owner sets one through the
 * FR-AUTH-007 reset link, so confirming sensitive changes still needs access to the mailbox.
 */
class OwnerPasswordSetupController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_if($user === null || $user->hasPassword(), 404);

        Password::sendResetLink(['email' => $user->email]);

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Link untuk membuat password sudah dikirim ke email Anda. Buka link itu, lalu masuk kembali.');
    }
}
