<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Subscription\CreateTrialSubscription;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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
    public function store(RegisterRequest $request, CreateTrialSubscription $createTrial): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'namalengkap' => $validated['name'],
            'email'       => $validated['email'],
            'password'    => Hash::make($validated['password']),
            'nomorhp'     => $validated['nomorhp'],
            'role'        => 'owner',
        ]);

        // FS-002 & FS-003: Buat Tenant dengan auto-generate slug dari nama bisnis
        $slug     = Str::slug($validated['nama_bisnis']);
        $slugBase = $slug;
        $counter  = 1;
        while (Tenant::where('slug', $slug)->exists()) {
            $slug = $slugBase . '-' . $counter++;
        }

        $tenant = Tenant::create([
            'iduser'      => $user->id,
            'namabisnis'  => $validated['nama_bisnis'],
            'jenisbisnis' => $validated['jenis_bisnis'],
            'alamat'      => $validated['alamat'],
            'nomorhp'     => $validated['nomorhp'],
            'slug'        => $slug,
        ]);

        // FS-018: Buat Subscription trial 7 hari setara paket Pro via CreateTrialSubscription
        $createTrial->execute($tenant);

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('current_tenant_id', $tenant->id);
        $user->sendEmailVerificationNotification();

        return redirect()->route('verification.notice');
    }
}
