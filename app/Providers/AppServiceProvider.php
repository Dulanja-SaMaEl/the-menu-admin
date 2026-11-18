<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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
        // Disable DB query logging in production to conserve shared hosting RAM
        if (app()->environment('production')) {
            DB::disableQueryLog();
        }

        // Tuned API Rate Limiting for 750-1000 mobile users
        // Keys on authenticated user ID first; falls back to IP with a generous 300 req/min
        // to prevent cellular carrier NAT gateways (e.g. mobile networks) from locking out users.
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(300)->by($request->user()?->id ?: $request->ip());
        });
    }
}
