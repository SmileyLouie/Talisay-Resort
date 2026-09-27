<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * The root URL now shows the landing page (not a redirect to login).
     */
    public function test_the_application_shows_landing_page_to_unauthenticated_user(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_the_login_page_is_accessible(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }
}
