<?php

declare(strict_types=1);

namespace App\Actions\Owner;

use App\Domain\Payout\PayoutAccountData;
use App\Models\Tenant;
use App\Models\TenantPayoutAccount;
use App\Models\User;
use App\Notifications\PayoutAccountChangedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveTenantPayoutAccount
{
    /**
     * Save or update a tenant's payout bank account under tenant lock.
     *
     * @throws ValidationException
     */
    public function execute(Tenant $tenant, PayoutAccountData $data, ?TenantPayoutAccount $existing = null): TenantPayoutAccount
    {
        return DB::transaction(function () use ($tenant, $data, $existing): TenantPayoutAccount {
            $lockedTenant = Tenant::query()->lockForUpdate()->find($tenant->id);
            if (! $lockedTenant) {
                throw ValidationException::withMessages([
                    'rekening' => 'Tenant tidak ditemukan.',
                ]);
            }

            $maxAccounts = (int) config('payouts.max_accounts', 3);
            $bankNama = (string) (config("banks.{$data->bankKode}") ?? $data->bankKode);

            // The unique index covers soft-deleted rows too (BQ-127), so match them explicitly.
            $sameNumber = TenantPayoutAccount::withTrashed()
                ->where('idtenant', $lockedTenant->id)
                ->where('bank_kode', $data->bankKode)
                ->where('nomor_rekening', $data->nomorRekening)
                ->when($existing !== null, fn ($query) => $query->whereKeyNot($existing->id))
                ->first();

            if ($sameNumber !== null && ! $sameNumber->trashed()) {
                throw ValidationException::withMessages([
                    'nomor_rekening' => 'Rekening ini sudah tersimpan.',
                ]);
            }

            if ($existing === null) {
                $count = TenantPayoutAccount::where('idtenant', $lockedTenant->id)->count();
                if ($count >= $maxAccounts) {
                    throw ValidationException::withMessages([
                        'rekening' => 'Maksimal '.$maxAccounts.' rekening tersimpan.',
                    ]);
                }

                $attributes = [
                    'idtenant' => $lockedTenant->id,
                    'bank_kode' => $data->bankKode,
                    'bank_nama' => $bankNama,
                    'nomor_rekening' => $data->nomorRekening,
                    'nama_pemilik_rekening' => $data->namaPemilik,
                    'is_default' => $count === 0,
                    'changed_at' => now(),
                ];

                if ($sameNumber !== null) {
                    // Re-adding a deleted account of this tenant restores that row.
                    $sameNumber->restore();
                    $sameNumber->fill($attributes)->save();
                    $account = $sameNumber;
                } else {
                    $account = TenantPayoutAccount::create($attributes);
                }
                $actionType = 'ditambahkan';
            } else {
                if ($sameNumber !== null) {
                    throw ValidationException::withMessages([
                        'nomor_rekening' => 'Rekening ini pernah dihapus. Tambahkan sebagai rekening baru untuk memulihkannya.',
                    ]);
                }

                $account = TenantPayoutAccount::where('idtenant', $lockedTenant->id)->findOrFail($existing->id);
                $hasChanged = $account->bank_kode !== $data->bankKode
                    || $account->nomor_rekening !== $data->nomorRekening
                    || $account->nama_pemilik_rekening !== $data->namaPemilik;

                $account->fill([
                    'bank_kode' => $data->bankKode,
                    'bank_nama' => $bankNama,
                    'nomor_rekening' => $data->nomorRekening,
                    'nama_pemilik_rekening' => $data->namaPemilik,
                ]);

                if ($hasChanged) {
                    $account->changed_at = now();
                }

                $account->save();
                $actionType = 'diubah';
            }

            DB::afterCommit(function () use ($lockedTenant, $actionType, $account): void {
                $owner = $lockedTenant->user;
                if ($owner instanceof User) {
                    $owner->notify(new PayoutAccountChangedNotification(
                        $actionType,
                        (string) $account->bank_nama,
                        $account->masked_number
                    ));
                }
            });

            return $account;
        });
    }
}
