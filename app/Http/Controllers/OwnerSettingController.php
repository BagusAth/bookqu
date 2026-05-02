<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;

class OwnerSettingController extends Controller
{
    /**
     * Halaman pengaturan bisnis.
     */
    public function index()
    {
        $tenant = Tenant::with('user')->first();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        return view('owner.owner-settings', compact('tenant'));
    }
}
