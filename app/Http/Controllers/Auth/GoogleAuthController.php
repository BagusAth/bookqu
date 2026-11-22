<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\GoogleSignInRejected;
use App\Actions\Auth\RegisterOwnerWithGoogle;
use App\Actions\Auth\ResolveGoogleSignIn;
use App\Http\Controllers\Concerns\FinishesSignIn;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CompleteGoogleRegistrationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Laravel\Socialite\AbstractUser;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

/**
 * Sign in and register with Google (FR-AUTH-009, BQ-172).
 */
class GoogleAuthController extends Controller
{
    use FinishesSignIn;

    private const PENDING_KEY = 'google_registration';

    private const PENDING_MINUTES = 30;

    private const FAILED_MESSAGE = 'Masuk dengan Google gagal. Coba lagi atau masuk dengan email dan password.';

    public function redirect(): SymfonyRedirectResponse
    {
        // The Google driver already requests only openid, profile and email.
        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request, ResolveGoogleSignIn $resolveGoogleSignIn): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $exception) {
            Log::warning('Google sign-in callback failed.', ['exception' => $exception::class]);

            return $this->failed();
        }

        $googleId = (string) $googleUser->getId();
        $email = (string) $googleUser->getEmail();
        $emailVerified = $googleUser instanceof AbstractUser
            && filter_var($googleUser->getRaw()['email_verified'] ?? false, FILTER_VALIDATE_BOOL);

        try {
            $user = $resolveGoogleSignIn->execute($googleId, $email, $emailVerified);
        } catch (GoogleSignInRejected) {
            return $this->failed();
        }

        if ($user === null) {
            $request->session()->put(self::PENDING_KEY, [
                'google_id' => $googleId,
                'email' => $email,
                'name' => (string) $googleUser->getName(),
                'expires_at' => now()->addMinutes(self::PENDING_MINUTES)->getTimestamp(),
            ]);

            return redirect()->route('auth.google.complete');
        }

        Auth::login($user);

        return $this->finishSignIn($request);
    }

    public function complete(Request $request): View|RedirectResponse
    {
        $pending = $this->pending($request);

        if ($pending === null) {
            return $this->failed();
        }

        return view('auth.register', ['googleProfile' => $pending]);
    }

    public function store(CompleteGoogleRegistrationRequest $request, RegisterOwnerWithGoogle $registerOwnerWithGoogle): RedirectResponse
    {
        $pending = $this->pending($request);

        if ($pending === null) {
            return $this->failed();
        }

        try {
            ['user' => $user, 'tenant' => $tenant] = $registerOwnerWithGoogle->execute($pending, $request->validated());
        } catch (GoogleSignInRejected) {
            return $this->failed();
        }

        $request->session()->forget(self::PENDING_KEY);
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('current_tenant_id', $tenant->id);

        return redirect()->route('owner.dashboard');
    }

    /**
     * @return array{google_id: string, email: string, name: string}|null
     */
    private function pending(Request $request): ?array
    {
        $pending = $request->session()->get(self::PENDING_KEY);

        if (! is_array($pending) || ($pending['expires_at'] ?? 0) < now()->getTimestamp()) {
            $request->session()->forget(self::PENDING_KEY);

            return null;
        }

        return [
            'google_id' => (string) $pending['google_id'],
            'email' => (string) $pending['email'],
            'name' => (string) $pending['name'],
        ];
    }

    private function failed(): RedirectResponse
    {
        return redirect()->route('login')->withErrors(['email' => self::FAILED_MESSAGE]);
    }
}
