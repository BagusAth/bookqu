<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'idtenant',
        'jenis',
        'jumlah',
        'periode',
    ];

    protected function casts(): array
    {
        return [
            'periode'    => 'date',
            'created_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'idtenant');
    }

    /**
     * Catat satu unit penggunaan (booking atau layanan) untuk tenant pada hari ini.
     *
     * @param int    $idtenant
     * @param string $jenis    'booking' | 'layanan'
     */
    public static function record(int $idtenant, string $jenis): void
    {
        static::create([
            'idtenant' => $idtenant,
            'jenis'    => $jenis,
            'jumlah'   => 1,
            'periode'  => now()->toDateString(),
        ]);
    }
}

