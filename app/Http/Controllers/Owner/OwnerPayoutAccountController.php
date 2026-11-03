<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Actions\Owner\DeleteTenantPayoutAccount;
use App\Actions\Owner\MakeDefaultPayoutAccount;
use App\Actions\Owner\SaveTenantPayoutAccount;
use App\Domain\Payout\PayoutAccountData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\DestroyPayoutAccountRequest;
use App\Http\Requests\Owner\StorePayoutAccountRequest;
use App\Http\Requests\Owner\UpdatePayoutAccountRequest;
use App\Models\TenantPayoutAccount;
use App\Traits\ResolvesOwnerTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OwnerPayoutAccountController extends Controller
{
    use ResolvesOwnerTenant;

    public function store(StorePayoutAccountRequest $request, SaveTenantPayoutAccount $action): RedirectResponse
    {
        $tenant = $this->resolveTenant();
        if (! $tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $data = new PayoutAccountData(
            bankKode: (string) $request->input('bank_kode'),
            nomorRekening: (string) $request->input('nomor_rekening'),
            namaPemilik: (string) $request->input('nama_pemilik_rekening'),
        );

        $action->execute($tenant, $data);

        return redirect()->route('owner.settings.balance')->with('sukses', 'Rekening penarikan berhasil ditambahkan.');
    }

    public function update(UpdatePayoutAccountRequest $request, int $account, SaveTenantPayoutAccount $action): RedirectResponse
    {
        $tenant = $this->resolveTenant();
        if (! $tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $accountModel = TenantPayoutAccount::where('idtenant', $tenant->id)->findOrFail($account);

        $data = new PayoutAccountData(
            bankKode: (string) $request->input('bank_kode'),
            nomorRekening: (string) $request->input('nomor_rekening'),
            namaPemilik: (string) $request->input('nama_pemilik_rekening'),
        );

        $action->execute($tenant, $data, $accountModel);

        return redirect()->route('owner.settings.balance')->with('sukses', 'Rekening penarikan berhasil diperbarui.');
    }

    public function destroy(DestroyPayoutAccountRequest $request, int $account, DeleteTenantPayoutAccount $action): RedirectResponse
    {
        $tenant = $this->resolveTenant();
        if (! $tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $accountModel = TenantPayoutAccount::where('idtenant', $tenant->id)->findOrFail($account);

        $action->execute($tenant, $accountModel);

        return redirect()->route('owner.settings.balance')->with('sukses', 'Rekening penarikan berhasil dihapus.');
    }

    public function makeDefault(Request $request, int $account, MakeDefaultPayoutAccount $action): RedirectResponse
    {
        $tenant = $this->resolveTenant();
        if (! $tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $accountModel = TenantPayoutAccount::where('idtenant', $tenant->id)->findOrFail($account);

        $action->execute($tenant, $accountModel);

        return redirect()->route('owner.settings.balance')->with('sukses', 'Rekening utama berhasil diubah.');
    }
}
