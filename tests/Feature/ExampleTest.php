<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase; // ★ここが重要！Illuminate... から始まっているか確認
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $this->withoutExceptionHandling(); // ← これを追加！
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
