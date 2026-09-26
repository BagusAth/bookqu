<?php

declare(strict_types=1);

namespace App\Actions\Owner;

use App\Models\Asset;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DeleteOwnerAccount
{
    /**
     * Delete owner account, associated tenant, assets, and storage files after confirmation.
     *
     * @throws ValidationException
     */
    public function execute(User $user, ?Tenant $tenant, string $confirmationInput): void
    {
        $expectedEmail = strtolower(trim($user->email));
        $expectedBusiness = strtolower(trim($tenant?->namabisnis ?? ''));
        $input = strtolower(trim($confirmationInput));

        if ($input === '' || ($input !== $expectedEmail && $input !== $expectedBusiness)) {
            throw ValidationException::withMessages([
                'confirm_account' => 'Konfirmasi tidak sesuai. Silakan ketikkan email akun (' . $user->email . ') dengan benar untuk konfirmasi penghapusan.',
            ]);
        }

        DB::transaction(function () use ($user, $tenant) {
            if ($tenant) {
                // Delete logo file
                if ($tenant->logo_path && !str_starts_with($tenant->logo_path, 'http') && Storage::disk('public')->exists($tenant->logo_path)) {
                    Storage::disk('public')->delete($tenant->logo_path);
                }
                // Delete banner file
                if ($tenant->banner_path && !str_starts_with($tenant->banner_path, 'http') && Storage::disk('public')->exists($tenant->banner_path)) {
                    Storage::disk('public')->delete($tenant->banner_path);
                }

                // Delete asset files
                $assets = Asset::where('idtenant', $tenant->id)->get();
                foreach ($assets as $asset) {
                    if ($asset->file_path && Storage::disk('public')->exists($asset->file_path)) {
                        Storage::disk('public')->delete($asset->file_path);
                    }
                }

                $tenant->delete();
            }

            $user->delete();
        });
    }
}
