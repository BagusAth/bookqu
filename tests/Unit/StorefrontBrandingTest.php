<?php

namespace Tests\Unit;

use App\Models\Tenant;
use App\Support\CategoryColor;
use App\Support\StorefrontBranding;
use PHPUnit\Framework\TestCase;

final class StorefrontBrandingTest extends TestCase
{
    public function test_brand_uses_readable_text_for_light_and_dark_colours(): void
    {
        $light = StorefrontBranding::for(new Tenant(['theme_color' => '#FDE047']));
        $dark = StorefrontBranding::for(new Tenant(['theme_color' => '#382186']));

        $this->assertSame('#000000', $light['onPrimary']);
        $this->assertSame('#FFFFFF', $dark['onPrimary']);
        foreach ([$light, $dark] as $branding) {
            $this->assertNotSame($branding['color'], $branding['hover']);
            $this->assertNotSame($branding['light'], $branding['border']);
        }
    }

    public function test_legacy_colours_share_the_category_palette_and_unknown_values_fall_back(): void
    {
        foreach (CategoryColor::keys() as $color) {
            $this->assertSame(strtoupper(CategoryColor::hex($color)), StorefrontBranding::for(new Tenant(['theme_color' => $color]))['color']);
        }

        $this->assertSame(StorefrontBranding::DEFAULT_COLOR, StorefrontBranding::for(new Tenant(['theme_color' => 'invalid']))['color']);
    }
}
