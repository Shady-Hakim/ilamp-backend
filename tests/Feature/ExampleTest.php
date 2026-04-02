<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_serves_the_static_frontend_when_published(): void
    {
        $response = $this->get('/');

        if (file_exists(public_path('index.html'))) {
            $response->assertOk();
            return;
        }

        $response->assertRedirect('/admin');
    }
}
