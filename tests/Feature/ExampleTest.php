<?php

namespace Tests\Feature;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->app->make(Kernel::class)->handle(
            Request::create('/', 'GET')
        );

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(route('login'), $response->headers->get('Location'));
    }

    public function test_the_login_page_is_accessible(): void
    {
        $response = $this->app->make(Kernel::class)->handle(
            Request::create('/login', 'GET')
        );

        $this->assertSame(200, $response->getStatusCode());
    }
}
