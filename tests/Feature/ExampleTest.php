<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * La home reindirizza alla dashboard (e quindi al login per gli ospiti).
     */
    public function test_the_application_redirects_from_home(): void
    {
        $response = $this->get('/');

        $response->assertRedirect();
    }
}
