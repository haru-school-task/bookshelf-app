<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Class ReportTest
 *
 * 新しく独立させたマイ読書レポート画面（ReportController）の正常な表示を検証し、
 * テストカバレッジを安全に引き上げるための機能テストクラスです。
 */
class ReportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 認証済みのユーザーが、新しく独立したマイ読書レポート画面に正常にアクセスできるかを検証します。
     */
    public function test_authenticated_user_can_view_report_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertStatus(200);
        $response->assertViewIs('reports.index');
        $response->assertViewHas('stats');
    }
}
