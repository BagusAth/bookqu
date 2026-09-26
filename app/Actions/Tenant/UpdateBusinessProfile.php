<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UpdateBusinessProfile
{
    /**
     * Update tenant business profile, slug, and branding logo.
     *
     * @param array<string, mixed> $data
     * @throws ValidationException
     */
    public function execute(User $user, ?Tenant $tenant, array $data, ?UploadedFile $logo = null): Tenant
    {
        $namabisnis = trim((string) $data['namabisnis']);

        // Preserve existing slug if business name is unchanged
        if ($tenant && $tenant->namabisnis === $namabisnis && !empty($tenant->slug)) {
            $slug = $tenant->slug;
        } else {
            $slug = Str::slug($namabisnis);

            if ($slug === '') {
                throw ValidationException::withMessages(['namabisnis' => 'Nama bisnis tidak valid untuk dijadikan URL.']);
            }

            $reserved = ['owner', 'admin', 'login', 'register'];
            if (in_array($slug, $reserved, true)) {
                throw ValidationException::withMessages(['namabisnis' => 'Nama bisnis ini tidak bisa dipakai sebagai URL.']);
            }

            $slugQuery = Tenant::where('slug', $slug);
            if ($tenant) {
                $slugQuery->where('id', '!=', $tenant->id);
            }

            if ($slugQuery->exists()) {
                throw ValidationException::withMessages(['namabisnis' => 'Slug sudah dipakai. Coba variasi nama bisnis lain.']);
            }
        }

        $logoPath = $tenant?->logo_path;
        if ($logo) {
            if ($tenant && $tenant->logo_path && !str_starts_with($tenant->logo_path, 'http') && Storage::disk('public')->exists($tenant->logo_path)) {
                Storage::disk('public')->delete($tenant->logo_path);
            }
            $logoPath = $logo->store('logos', 'public');
        }

        if ($tenant) {
            $tenant->update([
                'namabisnis'  => $namabisnis,
                'slug'        => $slug,
                'jenisbisnis' => $data['jenisbisnis'],
                'alamat'      => $data['alamat'],
                'deskripsi'   => $data['deskripsi'] ?? null,
                'logo_path'   => $logoPath,
                'nomorhp'     => $data['nomorhp'],
            ]);
        } else {
            $tenant = Tenant::create([
                'iduser'      => $user->id,
                'namabisnis'  => $namabisnis,
                'slug'        => $slug,
                'jenisbisnis' => $data['jenisbisnis'],
                'alamat'      => $data['alamat'],
                'deskripsi'   => $data['deskripsi'] ?? null,
                'logo_path'   => $logoPath,
                'nomorhp'     => $data['nomorhp'],
            ]);
        }

        app(TenantContext::class)->setTenantId($tenant->id);
        Cache::forget("tenant:slug:{$tenant->slug}");

        return $tenant;
    }
}
