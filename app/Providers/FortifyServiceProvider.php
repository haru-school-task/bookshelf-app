<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
// 💡 修正：末尾にセミコロン「;」を正しく追記しました
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Fortify\Contracts\LogoutResponse;
use Laravel\Fortify\Fortify;

// 💡 修正：消えてしまっていたクラス宣言の「枠組み」を正しく復活させました
class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 門番（登録担当者）をガチッと紐付け
        $this->app->singleton(CreatesNewUsers::class, CreateNewUser::class);

        // Fortifyに本物のお皿（Blade）の場所を教え込みます
        Fortify::loginView(function () {
            return view('auth.login');
        });

        Fortify::registerView(function () {
            return view('auth.register');
        });
    }

    public function register()
    {
        $this->app->instance(LogoutResponse::class, new class implements LogoutResponse
        {
            public function toResponse($request)
            {
                return redirect('/login');
            }
        });
    }
}
