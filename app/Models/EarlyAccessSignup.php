<?php

namespace App\Models;

use Database\Factories\EarlyAccessSignupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A business that signed up on the public early access page (BQ-175, FR-PUBLIC-009).
 * Platform data, not tenant-owned.
 *
 * @property int $id
 * @property string $full_name
 * @property string $business_name
 * @property string $business_type
 * @property string $email
 * @property string $whatsapp_number
 */
class EarlyAccessSignup extends Model
{
    /** @use HasFactory<EarlyAccessSignupFactory> */
    use HasFactory;

    protected $fillable = [
        'full_name',
        'business_name',
        'business_type',
        'email',
        'whatsapp_number',
    ];

    /**
     * WhatsApp chat link in international format (`08…` → `628…`).
     */
    public function whatsappUrl(): string
    {
        return 'https://wa.me/62'.substr($this->whatsapp_number, 1);
    }
}
