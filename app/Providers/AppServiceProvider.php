<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\PersonalAccessToken;

class AppServiceProvider extends ServiceProvider
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
        // per token, so one runaway script doesn't lock out the others; guests per IP
        RateLimiter::for('api', function (Request $request) {
            $token = $request->user()?->currentAccessToken();

            return Limit::perMinute(120)->by($token instanceof PersonalAccessToken ? "token:{$token->id}" : $request->ip());
        });
    }
}
