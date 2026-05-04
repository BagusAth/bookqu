<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DummyRegistrationController extends Controller
{
    public function showForm()
    {
        return view('dummy-register');
    }

    public function processForm(Request $request)
    {
        $data = $request->validate([
            'namabisnis' => 'required|string|max:150',
        ]);

        $namabisnis = trim($data['namabisnis']);
        $slug = Str::slug($namabisnis);

        if ($slug === '') {
            return back()
                ->withErrors(['namabisnis' => 'Nama bisnis tidak valid untuk dijadikan URL.'])
                ->withInput();
        }

        // Prevent collisions with existing routes.
        $reserved = ['owner', 'dummy-register'];
        if (in_array($slug, $reserved, true)) {
            return back()
                ->withErrors(['namabisnis' => 'Nama bisnis ini tidak bisa dipakai sebagai URL.'])
                ->withInput();
        }

        if (Tenant::where('slug', $slug)->exists()) {
            return back()
                ->withErrors(['namabisnis' => 'Slug sudah dipakai. Coba variasi nama bisnis lain.'])
                ->withInput();
        }

        // Create dummy user + tenant together to keep data consistent.
        $tenant = DB::transaction(function () use ($namabisnis, $slug) {
            $email = $this->generateUniqueEmail();

            $user = new User();
            $user->namalengkap = 'Dummy Owner ' . $namabisnis;
            $user->email = $email;
            $user->password = Str::random(12);
            $user->nomorhp = '080000000000';
            $user->role = 'owner';
            $user->save();

            return Tenant::create([
                'iduser' => $user->id,
                'namabisnis' => $namabisnis,
                'slug' => $slug,
                'jenisbisnis' => 'Dummy Business',
                'alamat' => 'Alamat Dummy',
                'nomorhp' => '080000000000',
            ]);
        });

        return redirect('/' . $tenant->slug);
    }

    public function welcomePage(string $slug)
    {
        $tenant = Tenant::where('slug', $slug)->firstOrFail();

        return view('dummy-welcome', compact('tenant'));
    }

    private function generateUniqueEmail(): string
    {
        do {
            $email = 'dummy-' . Str::random(10) . '@dummy.bookqu.test';
        } while (User::where('email', $email)->exists());

        return $email;
    }
}
