<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class MobileHeaderLocationBannerTest extends TestCase
{
    public function test_mobile_header_uses_gps_location_banner(): void
    {
        $html = Blade::render('<x-mobile-header :showSearch="false" />');

        $this->assertStringContainsString('x-data="locationBanner()"', $html);
        $this->assertStringContainsString("x-text=\"loading ? 'Mendeteksi lokasi...' : label\"", $html);
        $this->assertStringContainsString('Area Anda:', $html);
        $this->assertStringContainsString('Jabodetabek & Sekitarnya', $html);
        $this->assertStringContainsString('Ubah', $html);
    }
}
