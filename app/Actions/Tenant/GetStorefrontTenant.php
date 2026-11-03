<?php

namespace App\Actions\Tenant;

use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;

final class GetStorefrontTenant
{
    private const FIELDS = [
        'id', 'iduser', 'namabisnis', 'slug', 'custom_domain', 'jenisbisnis', 'alamat',
        'deskripsi', 'logo_path', 'banner_path', 'nomorhp', 'weekend_price_type',
        'weekend_price_value', 'cancel_before_hours', 'reschedule_before_hours',
        'theme_color', 'button_style', 'font_family', 'card_style',
    ];

    public function execute(int $tenantId): ?Tenant
    {
        $data = Cache::remember($this->key($tenantId), 3600, fn () => Tenant::query()->select(self::FIELDS)->find($tenantId)?->getAttributes()
        );

        return is_array($data) ? Tenant::hydrate([$data])->first() : null;
    }

    public function forget(int $tenantId): void
    {
        Cache::forget($this->key($tenantId));
    }

    private function key(int $tenantId): string
    {
        return 'tenant:public:v2:'.$tenantId;
    }
}
