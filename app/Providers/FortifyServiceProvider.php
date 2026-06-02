<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Fortify\Contracts\LogoutResponse;
use Laravel\Fortify\Fortify;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->singleton(CreatesNewUsers::class, CreateNewUser::class);

        Fortify::loginView(function () {
            return view('auth.login');
        });

        Fortify::registerView(function () {
            return view('auth.register');
        });

        
        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;
            
            return Limit::perMinute(10)->by($email . $request->ip());
        });

        
        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
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
