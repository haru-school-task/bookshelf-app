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
     * 
     * @return void
     */
    public function test_authenticated_user_can_view_report_page(): void
    {
        // テスト用のユーザーを作成
        $user = User::factory()->create();

        // ログインした状態で、新しくグループ化した /reports へアクセス
        $response = $this->actingAs($user)->get(route('reports.index'));

        // 💡 画面が 200 OK で正常に表示され、新コントローラーが動いていることを検証
        $response->assertStatus(200);
        $response->assertViewIs('reports.index');
        $response->assertViewHas('stats'); // 既存のBladeに渡している変数の存在確認
    }
}
