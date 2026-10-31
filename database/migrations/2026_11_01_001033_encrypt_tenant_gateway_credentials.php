<?php

use Illuminate\Cache\DatabaseStore;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const COLUMNS = [
        'midtrans_sandbox_client_key', 'midtrans_sandbox_server_key',
        'midtrans_prod_client_key', 'midtrans_prod_server_key',
    ];

    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            foreach (self::COLUMNS as $column) {
                $table->text($column)->nullable()->change();
            }
        });
        DB::transaction(function (): void {
            $this->transform(encrypt: true);
            $this->purgeTenantCache();
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            $this->transform(encrypt: false);
            $this->purgeTenantCache();
        });
        // Keep the compatible TEXT expansion: shrinking during rollback could truncate new values.
    }

    private function transform(bool $encrypt): void
    {
        DB::transaction(function () use ($encrypt): void {
            DB::table('tenants')->select(['id', ...self::COLUMNS])->orderBy('id')->chunkById(100, function ($rows) use ($encrypt): void {
                foreach ($rows as $row) {
                    $values = [];
                    foreach (self::COLUMNS as $column) {
                        $value = $row->{$column};
                        if ($value === null) {
                            continue;
                        }
                        if ($encrypt) {
                            try {
                                Crypt::decryptString($value);

                                continue;
                            } catch (DecryptException) {
                                $values[$column] = Crypt::encryptString($value);
                            }
                        } else {
                            $values[$column] = Crypt::decryptString($value);
                        }
                    }
                    if ($values !== []) {
                        DB::table('tenants')->where('id', $row->id)->update($values);
                    }
                }
            });
        });
    }

    private function purgeTenantCache(): void
    {
        $store = Cache::getStore();
        if ($store instanceof DatabaseStore) {
            $connection = $store->getConnection();
            $column = $connection->getQueryGrammar()->wrap('key');
            foreach (['tenant:slug:', 'tenant:public:v2:'] as $namespace) {
                $prefix = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $store->getPrefix().$namespace);
                $connection->table(config('cache.stores.'.config('cache.default').'.table', 'cache'))
                    ->whereRaw($column." LIKE ? ESCAPE '!'", [$prefix.'%'])->delete();
            }

            return;
        }

        DB::table('tenants')->select(['id', 'slug', 'custom_domain'])->orderBy('id')->each(function ($tenant): void {
            Cache::forget('tenant:public:v2:'.$tenant->id);
            foreach (array_filter([$tenant->slug, $tenant->custom_domain]) as $identifier) {
                Cache::forget('tenant:slug:'.$identifier);
            }
        });
    }
};
