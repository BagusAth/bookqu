<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Cache;

class UpdatePaymentSettings
{
    /**
     * Update Midtrans payment gateway settings for tenant.
     *
     * @param array<string, mixed> $data
     */
    public function execute(Tenant $tenant, array $data): Tenant
    {
        app(TenantContext::class)->setTenantId($tenant->id);

        $tenant->payment_mode = $data['payment_mode'];
        $tenant->midtrans_environment = $data['midtrans_environment'];

        if (isset($data['midtrans_sandbox_merchant_id'])) {
            $tenant->midtrans_sandbox_merchant_id = $data['midtrans_sandbox_merchant_id'];
        }
        if (isset($data['midtrans_sandbox_client_key'])) {
            $tenant->midtrans_sandbox_client_key = $data['midtrans_sandbox_client_key'];
        }
        if (isset($data['midtrans_sandbox_server_key']) && $data['midtrans_sandbox_server_key'] !== '********') {
            $tenant->midtrans_sandbox_server_key = $data['midtrans_sandbox_server_key'];
        }

        if (isset($data['midtrans_prod_merchant_id'])) {
            $tenant->midtrans_prod_merchant_id = $data['midtrans_prod_merchant_id'];
        }
        if (isset($data['midtrans_prod_client_key'])) {
            $tenant->midtrans_prod_client_key = $data['midtrans_prod_client_key'];
        }
        if (isset($data['midtrans_prod_server_key']) && $data['midtrans_prod_server_key'] !== '********') {
            $tenant->midtrans_prod_server_key = $data['midtrans_prod_server_key'];
        }

        if ($tenant->payment_mode === 'owner') {
            $tenant->midtrans_status = 'pending';
        }

        $tenant->save();

        Cache::forget("tenant:slug:{$tenant->slug}");

        return $tenant;
    }
}
