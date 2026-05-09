<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OwnerSettingController extends Controller
{
    /**
     * Halaman pengaturan bisnis.
     */
    public function index()
    {
        $iduser = Auth::id();
        $tenant = Tenant::with('user')->where('iduser', $iduser)->first();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        return view('owner.owner-settings', compact('tenant'));
    }
}
