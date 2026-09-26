<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateInitialProfile
{
    /**
     * Store initial business profile and setup free trial subscription.
     *
     * @param array<string, mixed> $data
     * @throws ValidationException
     */
    public function execute(User $user, array $data, ?Tenant $existingTenant = null): Tenant
    {
        $namabisnis = trim((string) $data['namabisnis']);
        $slug = Str::slug($namabisnis);

        if ($slug === '') {
            throw ValidationException::withMessages(['namabisnis' => 'Nama bisnis tidak valid untuk dijadikan URL.']);
        }

        $reserved = ['owner', 'admin', 'login', 'register'];
        if (in_array($slug, $reserved, true)) {
            throw ValidationException::withMessages(['namabisnis' => 'Nama bisnis ini tidak bisa dipakai sebagai URL.']);
        }

        $slugQuery = Tenant::where('slug', $slug);
        if ($existingTenant) {
            $slugQuery->where('id', '!=', $existingTenant->id);
        }

        if ($slugQuery->exists()) {
            throw ValidationException::withMessages(['namabisnis' => 'Slug sudah dipakai. Coba variasi nama bisnis lain.']);
        }

        $isNewTenant = !Tenant::where('iduser', $user->id)->exists();

        $tenant = Tenant::updateOrCreate(
            ['iduser' => $user->id],
            [
                'namabisnis'  => $namabisnis,
                'slug'        => $slug,
                'jenisbisnis' => $data['jenisbisnis'],
                'alamat'      => $data['alamat'],
                'nomorhp'     => $data['nomorhp'],
            ]
        );

        if ($isNewTenant) {
            $proPlan = Plan::firstOrCreate(
                ['namapaket' => 'pro'],
                [
                    'hargabulanan' => 100000,
                    'maxlayanan'   => 10,
                    'maxbooking'   => 500,
                    'isunlimited'  => false,
                ]
            );

            Subscription::create([
                'idtenant'       => $tenant->id,
                'idplan'         => $proPlan->id,
                'status'         => 'trial',
                'trial_berakhir' => now()->addDays(7),
            ]);
        }

        app(TenantContext::class)->setTenantId($tenant->id);

        return $tenant;
    }
}
