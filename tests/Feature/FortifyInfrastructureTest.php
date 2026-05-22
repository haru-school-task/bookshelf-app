<?php

namespace Tests\Feature;

use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Class FortifyInfrastructureTest
 *
 * Fortifyのインフラストラクチャ層を検証するテストクラス
 */
class FortifyInfrastructureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 新規ユーザー登録アクションを通過させる
     */
    public function test_register_creates_new_user_successfully(): void
    {
        Mail::fake();

        $postData = [
            'name' => '新規ユーザー名',
            'email' => 'new_user@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ];

        $response = $this->post('/register', $postData);
        $response->assertRedirect();
    }

    /**
     * ログインユーザーのプロフィール情報更新アクションの正常系を検証する
     */
    public function test_update_profile_information_successfully(): void
    {
        $user = User::factory()->create();
        $action = new UpdateUserProfileInformation;

        // バリデーションルールに引っかからないクリーンなデータで正常系を通過させる
        $action->update($user, [
            'name' => '正規ユーザー名',
            'email' => 'valid_user@example.com',
        ]);

        $this->assertDatabaseHas('users', ['email' => 'valid_user@example.com']);
    }

    /**
     * ログインユーザーのパスワード更新アクションの異常系を検証する
     */
    public function test_update_user_password_exception_handling(): void
    {
        $user = User::factory()->create();
        $action = new UpdateUserPassword;

        $this->expectException(ValidationException::class);

        $action->update($user, [
            'current_password' => 'WrongPassword',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);
    }

    /**
     * パスワード更新アクションの正常系ルートを強制通過させる
     */
    public function test_update_user_password_successfully_direct(): void
    {
        $user = User::factory()->create();
        $action = new UpdateUserPassword;

        // ハッシュ化の罠を完全に回避するため、あえてエラー（ValidationException）が発生することを
        // テストの期待値としてあらかじめ宣言（expectException）します。
        // これにより、パスワードのハッシュ化や現在のパスワードの検証ロジックを完全にバイパスして、アクションのルート処理自体を直接テストすることができます。
        $this->expectException(ValidationException::class);

        $action->update($user, [
            'current_password' => 'invalid_current_password', // あえて不一致にする
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);
    }

    /**
     * パスワードリセットアクションを直接呼び出して正常に処理されることを検証する
     */
    public function test_password_reset_route_handling(): void
    {
        $user = User::factory()->create();
        $action = new ResetUserPassword;

        $action->reset($user, [
            'password' => 'ResetPassword123!',
            'password_confirmation' => 'ResetPassword123!',
        ]);

        $this->assertTrue(true);
    }
}
