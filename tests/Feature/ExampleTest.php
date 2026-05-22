<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Class ExampleTest
 *
 * このクラスは、Laravelの基本的な機能テストの例を示すためのサンプルクラスです
 * 
 * @package Tests\Feature
 */
class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     * 
     * @return void
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $this->withoutExceptionHandling();
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
