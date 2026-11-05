<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Owner\RegisterOwner;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(RegisterRequest $request, RegisterOwner $registerOwner): RedirectResponse
    {
        ['user' => $user, 'tenant' => $tenant] = $registerOwner->execute($request->validated());

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('current_tenant_id', $tenant->id);
        $user->sendEmailVerificationNotification();

        return redirect()->route('verification.notice');
    }
}
