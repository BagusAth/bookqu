<?php

declare(strict_types=1);

namespace App\Actions\Owner;

use App\Actions\Subscription\CreateTrialSubscription;
use App\Models\Tenant;
use App\Models\User;
use App\Support\ReservedTenantSlugs;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RegisterOwner
{
    public function __construct(
        protected CreateTrialSubscription $createTrial
    ) {}

    /**
     * Create the owner, their tenant and the 7-day trial in one transaction (BQ-112), so a
     * failure at any step leaves no user without a tenant.
     *
     * Registration through Google (D-029) passes `google_id` instead of `password`: the account
     * has no password and its email is already verified by Google.
     *
     * @param  array{name: string, email: string, password?: string, google_id?: string, nomorhp: string, nama_bisnis: string, jenis_bisnis: string, alamat: string}  $data
     * @return array{user: User, tenant: Tenant}
     */
    public function execute(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $user = User::create([
                'namalengkap' => $data['name'],
                'email' => $data['email'],
                'password' => isset($data['password']) ? Hash::make($data['password']) : null,
                'nomorhp' => $data['nomorhp'],
                'role' => 'owner',
            ]);

            if (isset($data['google_id'])) {
                $user->forceFill(['google_id' => $data['google_id'], 'email_verified_at' => now()])->save();
            }

            // FS-002 & FS-003: tenant slug generated from the business name
            $slug = Str::slug($data['nama_bisnis']) ?: 'bisnis';
            $slugBase = $slug;
            $counter = 1;
            // Reserved words would be shadowed by an application route (BQ-011)
            while (ReservedTenantSlugs::contains($slug) || Tenant::where('slug', $slug)->exists()) {
                $slug = $slugBase.'-'.$counter++;
            }

            $tenant = Tenant::create([
                'iduser' => $user->id,
                'namabisnis' => $data['nama_bisnis'],
                'jenisbisnis' => $data['jenis_bisnis'],
                'alamat' => $data['alamat'],
                'nomorhp' => $data['nomorhp'],
                'slug' => $slug,
            ]);

            // FS-018: 7-day trial equal to the Pro plan
            $this->createTrial->execute($tenant);

            return ['user' => $user, 'tenant' => $tenant];
        });
    }
}
