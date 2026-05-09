<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OwnerAuthController extends Controller
{
    public function showlogin()
    {
        return view('owner.owner-login');
    }

    public function showregister()
    {
        return view('owner.owner-register');
    }

    public function login(Request $request)
    {
        $datavalid = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $ingat = $request->boolean('ingat');

        $berhasil = Auth::attempt([
            'email' => $datavalid['email'],
            'password' => $datavalid['password'],
            'role' => 'owner',
        ], $ingat);

        if ($berhasil) {
            $request->session()->regenerate();

            return redirect()->intended(route('owner.dashboard'));
        }

        return back()
            ->withErrors(['email' => 'Email atau password tidak valid.'])
            ->withInput();
    }

    public function register(Request $request)
    {
        $datavalid = $request->validate([
            'namabisnis' => 'required|string|max:150',
            'jenisbisnis' => 'required|string|max:100',
            'alamat' => 'required|string',
            'nomorhp' => 'required|string|max:20',
            'email' => 'required|email|max:100|unique:users,email',
            'password' => 'required|string|min:8',
            'konfirmasipassword' => 'required|same:password',
        ]);

        $userbaru = null;

        DB::transaction(function () use ($datavalid, &$userbaru) {
            $userbaru = User::create([
                'namalengkap' => $datavalid['namabisnis'],
                'email' => $datavalid['email'],
                'password' => Hash::make($datavalid['password']),
                'nomorhp' => $datavalid['nomorhp'],
                'role' => 'owner',
            ]);

            $slugdasar = Str::slug($datavalid['namabisnis']);
            $slugbisnis = $slugdasar ?: Str::random(8);
            $hitung = 2;

            while (Tenant::where('slug', $slugbisnis)->exists()) {
                $slugbisnis = $slugdasar . '-' . $hitung;
                $hitung++;
            }

            Tenant::create([
                'iduser' => $userbaru->id,
                'namabisnis' => $datavalid['namabisnis'],
                'slug' => $slugbisnis,
                'jenisbisnis' => $datavalid['jenisbisnis'],
                'alamat' => $datavalid['alamat'],
                'nomorhp' => $datavalid['nomorhp'],
            ]);
        });

        Auth::login($userbaru);

        return redirect()->route('owner.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('owner.login');
    }
}
