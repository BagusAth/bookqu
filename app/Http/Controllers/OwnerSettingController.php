<?php

namespace App\Http\Controllers;

use App\Models\OwnerPayout;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OwnerSettingController extends Controller
{
    private function resolveTenant(): ?Tenant
    {
        $userId = auth()->id();
        $tenantId = session('current_tenant_id');

        if (is_numeric($tenantId)) {
            $tenant = Tenant::with('user')->find($tenantId);
            if ($tenant && $tenant->iduser === $userId) {
                return $tenant;
            }
        }

        if ($userId) {
            return Tenant::with('user')->where('iduser', $userId)->first();
        }

        return null;
    }

    /**
     * Halaman pengaturan bisnis.
     */
    public function index()
    {
        $tenant = $this->resolveTenant();
        $user = auth()->user();

        if (!$tenant) {
            $tenant = new Tenant();
            if ($user) {
                $tenant->setRelation('user', $user);
            }
        }

        $payouts = collect();
        if ($tenant->id) {
            $payouts = OwnerPayout::where('idtenant', $tenant->id)
                ->orderByDesc('requested_at')
                ->limit(10)
                ->get();
        }

        return view('owner.owner-settings', compact('tenant', 'payouts'));
    }

    /**
     * Simpan profil bisnis untuk owner baru.
     */
    public function storeProfile(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            abort(403, 'User tidak ditemukan.');
        }

        $tenant = $this->resolveTenant();

        $data = $request->validate([
            'namabisnis' => 'required|string|max:150',
            'jenisbisnis' => 'required|string|max:150',
            'nomorhp' => 'required|string|max:20',
            'alamat' => 'required|string|max:255',
        ]);

        $namabisnis = trim($data['namabisnis']);
        $slug = Str::slug($namabisnis);

        if ($slug === '') {
            return back()
                ->withErrors(['namabisnis' => 'Nama bisnis tidak valid untuk dijadikan URL.'])
                ->withInput();
        }

        $reserved = ['owner', 'admin', 'login', 'register', 'dummy-register'];
        if (in_array($slug, $reserved, true)) {
            return back()
                ->withErrors(['namabisnis' => 'Nama bisnis ini tidak bisa dipakai sebagai URL.'])
                ->withInput();
        }

        $slugQuery = Tenant::where('slug', $slug);
        if ($tenant) {
            $slugQuery->where('id', '!=', $tenant->id);
        }

        if ($slugQuery->exists()) {
            return back()
                ->withErrors(['namabisnis' => 'Slug sudah dipakai. Coba variasi nama bisnis lain.'])
                ->withInput();
        }

        $isNewTenant = !Tenant::where('iduser', $user->id)->exists();

        $tenant = Tenant::updateOrCreate(
            ['iduser' => $user->id],
            [
                'namabisnis' => $namabisnis,
                'slug' => $slug,
                'jenisbisnis' => $data['jenisbisnis'],
                'alamat' => $data['alamat'],
                'nomorhp' => $data['nomorhp'],
            ]
        );

        if ($isNewTenant) {
            $proPlan = \App\Models\Plan::firstOrCreate(
                ['namapaket' => 'pro'],
                [
                    'hargabulanan' => 100000,
                    'maxlayanan' => 10,
                    'maxbooking' => 500,
                    'isunlimited' => false,
                ]
            );

            \App\Models\Subscription::create([
                'idtenant' => $tenant->id,
                'idplan' => $proPlan->id,
                'status' => 'trial',
                'trial_berakhir' => now()->addDays(7),
            ]);
        }

        session()->put('current_tenant_id', $tenant->id);

        return redirect()->route('owner.dashboard')->with('sukses', 'Profil bisnis berhasil dibuat. Anda mendapatkan Free Trial 7 Hari paket Pro!');
    }

    public function updateBusinessProfile(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            abort(403, 'User tidak ditemukan.');
        }

        $tenant = $this->resolveTenant();

        $data = $request->validate([
            'namabisnis' => 'required|string|max:150',
            'jenisbisnis' => 'required|string|max:150',
            'nomorhp' => 'required|string|max:20',
            'alamat' => 'required|string|max:255',
            'deskripsi' => 'nullable|string|max:1000',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $namabisnis = trim($data['namabisnis']);
        $slug = Str::slug($namabisnis);

        if ($slug === '') {
            return back()
                ->withErrors(['namabisnis' => 'Nama bisnis tidak valid untuk dijadikan URL.'])
                ->withInput();
        }

        $reserved = ['owner', 'admin', 'login', 'register', 'dummy-register'];
        if (in_array($slug, $reserved, true)) {
            return back()
                ->withErrors(['namabisnis' => 'Nama bisnis ini tidak bisa dipakai sebagai URL.'])
                ->withInput();
        }

        $slugQuery = Tenant::where('slug', $slug);
        if ($tenant) {
            $slugQuery->where('id', '!=', $tenant->id);
        }

        if ($slugQuery->exists()) {
            return back()
                ->withErrors(['namabisnis' => 'Slug sudah dipakai. Coba variasi nama bisnis lain.'])
                ->withInput();
        }

        $logoPath = $tenant?->logo_path;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('logos', 'public');
        }

        $tenant = Tenant::updateOrCreate(
            ['iduser' => $user->id],
            [
                'namabisnis' => $namabisnis,
                'slug' => $slug,
                'jenisbisnis' => $data['jenisbisnis'],
                'alamat' => $data['alamat'],
                'deskripsi' => $data['deskripsi'] ?? null,
                'logo_path' => $logoPath,
                'nomorhp' => $data['nomorhp'],
            ]
        );

        session()->put('current_tenant_id', $tenant->id);

        \Illuminate\Support\Facades\Cache::forget("tenant:slug:{$tenant->slug}");

        return redirect()->route('owner.settings')->with('sukses', 'Profil bisnis berhasil diperbarui.');
    }

    public function updateAccount(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            abort(403, 'User tidak ditemukan.');
        }

        $data = $request->validate([
            'namalengkap' => 'required|string|max:100',
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($user->id)],
            'nomorhp' => 'required|string|max:20',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $user->namalengkap = $data['namalengkap'];
        $user->email = $data['email'];
        $user->nomorhp = $data['nomorhp'];

        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return redirect()->route('owner.settings')->with('sukses', 'Akun berhasil diperbarui.');
    }

    public function updatePaymentSettings(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            abort(403, 'User tidak ditemukan.');
        }

        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $data = $request->validate([
            'payment_mode' => 'required|in:platform,owner',
            'midtrans_environment' => 'required|in:sandbox,production',
            'midtrans_sandbox_merchant_id' => 'nullable|string|max:100',
            'midtrans_sandbox_client_key' => 'nullable|string|max:200',
            'midtrans_sandbox_server_key' => 'nullable|string|max:200',
            'midtrans_prod_merchant_id' => 'nullable|string|max:100',
            'midtrans_prod_client_key' => 'nullable|string|max:200',
            'midtrans_prod_server_key' => 'nullable|string|max:200',
        ]);

        $tenant->payment_mode = $data['payment_mode'];
        $tenant->midtrans_environment = $data['midtrans_environment'];
        $tenant->midtrans_sandbox_merchant_id = $data['midtrans_sandbox_merchant_id'] ?? null;
        $tenant->midtrans_sandbox_client_key = $data['midtrans_sandbox_client_key'] ?? null;
        $tenant->midtrans_sandbox_server_key = $data['midtrans_sandbox_server_key'] ?? null;
        $tenant->midtrans_prod_merchant_id = $data['midtrans_prod_merchant_id'] ?? null;
        $tenant->midtrans_prod_client_key = $data['midtrans_prod_client_key'] ?? null;
        $tenant->midtrans_prod_server_key = $data['midtrans_prod_server_key'] ?? null;

        if ($tenant->payment_mode === 'owner') {
            $tenant->midtrans_status = 'pending';
        }

        $tenant->save();

        \Illuminate\Support\Facades\Cache::forget("tenant:slug:{$tenant->slug}");

        return redirect()->route('owner.settings')->with('sukses', 'Pengaturan pembayaran berhasil diperbarui.');
    }

    public function requestPayout(Request $request)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $data = $request->validate([
            'jumlah' => 'required|numeric|min:10000',
        ]);

        if ($tenant->payment_mode !== 'platform') {
            return back()->withErrors(['jumlah' => 'Withdraw hanya tersedia untuk mode pembayaran platform.']);
        }

        if ($tenant->saldo_platform < $data['jumlah']) {
            return back()->withErrors(['jumlah' => 'Saldo tidak mencukupi.']);
        }

        $tenant->saldo_platform = $tenant->saldo_platform - $data['jumlah'];
        $tenant->save();

        OwnerPayout::create([
            'idtenant' => $tenant->id,
            'jumlah' => $data['jumlah'],
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        return redirect()->route('owner.settings')->with('sukses', 'Permintaan withdraw berhasil dibuat.');
    }
}
