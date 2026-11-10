<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Models\Tenant;
use App\Support\CustomerBookingRoutes;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * The QR code of the business's current booking link (FR-PUBLIC-006, AC-HBK-02), as SVG.
 */
final class BuildBookingQr
{
    public const SIZE = 320;

    public const MARGIN = 2;

    public function link(Tenant $tenant): string
    {
        return CustomerBookingRoutes::url('customer.booking.program', $tenant->slug);
    }

    public function svg(Tenant $tenant): string
    {
        return (new Writer(new ImageRenderer(new RendererStyle(self::SIZE, self::MARGIN), new SvgImageBackEnd)))
            ->writeString($this->link($tenant));
    }
}
