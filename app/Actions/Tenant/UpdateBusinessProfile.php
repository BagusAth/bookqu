<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Models\Tenant;
use App\Models\User;
use App\Support\ReservedTenantSlugs;
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
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function execute(User $user, ?Tenant $tenant, array $data, ?UploadedFile $logo = null): Tenant
    {
        $namabisnis = trim((string) $data['namabisnis']);

        $oldSlug = $tenant?->slug;
        $explicitSlug = array_key_exists('slug', $data);
        $slug = $explicitSlug ? (string) $data['slug']
            : ($tenant?->namabisnis === $namabisnis && filled($oldSlug) ? $oldSlug : Str::slug($namabisnis));
        $slugField = $explicitSlug ? 'slug' : 'namabisnis';
        if ($slug !== $oldSlug) {
            if ($slug === '' || ReservedTenantSlugs::contains($slug)) {
                throw ValidationException::withMessages([$slugField => 'Alamat booking ini tidak dapat dipakai.']);
            }
            if (Tenant::where('slug', $slug)->exists()) {
                throw ValidationException::withMessages([$slugField => 'Alamat booking sudah dipakai.']);
            }
        }

        $contacts = array_intersect_key($data, ['business_email' => true, 'map_url' => true]);
        $logoPath = $tenant?->logo_path;
        if ($logo) {
            if ($tenant && $tenant->logo_path && ! str_starts_with($tenant->logo_path, 'http') && Storage::disk('public')->exists($tenant->logo_path)) {
                Storage::disk('public')->delete($tenant->logo_path);
            }
            $logoPath = $logo->store('logos', 'public');
        }

        if ($tenant) {
            $tenant->update([
                'namabisnis' => $namabisnis,
                'slug' => $slug,
                'jenisbisnis' => $data['jenisbisnis'],
                'alamat' => $data['alamat'],
                'deskripsi' => $data['deskripsi'] ?? null,
                'logo_path' => $logoPath,
                'nomorhp' => $data['nomorhp'],
            ] + $contacts);
        } else {
            $tenant = Tenant::create([
                'iduser' => $user->id,
                'namabisnis' => $namabisnis,
                'slug' => $slug,
                'jenisbisnis' => $data['jenisbisnis'],
                'alamat' => $data['alamat'],
                'deskripsi' => $data['deskripsi'] ?? null,
                'logo_path' => $logoPath,
                'nomorhp' => $data['nomorhp'],
            ] + $contacts);
        }

        app(TenantContext::class)->setTenantId($tenant->id);
        if ($oldSlug !== null && $oldSlug !== $tenant->slug) {
            Cache::forget("tenant:slug:{$oldSlug}");
        }
        Cache::forget("tenant:slug:{$tenant->slug}");

        app(GetStorefrontTenant::class)->forget((int) $tenant->id);

        return $tenant;
    }
}
