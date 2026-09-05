<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    public function test_terms_page_returns_successful_response(): void
    {
        $response = $this->get('/terms');

        $response->assertStatus(200);
        $response->assertSeeText('Syarat & Ketentuan');
        $response->assertSeeText('Perjanjian Layanan');
        $response->assertSeeText('Kebijakan Pembatalan & Refund');
    }

    public function test_privacy_page_returns_successful_response(): void
    {
        $response = $this->get('/privacy');

        $response->assertStatus(200);
        $response->assertSeeText('Kebijakan Privasi');
        $response->assertSeeText('Komitmen Keamanan Data');
        $response->assertSeeText('UU PDP');
    }

    public function test_register_page_contains_terms_and_privacy_links(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertSee(route('terms'));
        $response->assertSee(route('privacy'));
    }
}
