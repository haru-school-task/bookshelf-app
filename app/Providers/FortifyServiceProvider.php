<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
// 💡 修正：末尾にセミコロン「;」を正しく追記しました
use Laravel\Fortify\Contracts\CreatesNewUsers; 
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;

// 💡 修正：消えてしまっていたクラス宣言の「枠組み」を正しく復活させました
class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

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
}
