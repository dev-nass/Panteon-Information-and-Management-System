<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
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
        $this->configureRateLimiting();
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(10)->by($request->input('email') . '|' . $request->ip())
                ->response(function () {
                    return back()->withErrors([
                        'email' => 'Too many login attempts. Please try again in a few moments.',
                    ]);
                });
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perHour(10)->by($request->ip())
                ->response(function () {
                    return back()->withErrors([
                        'email' => 'Too many registration attempts. Please try again later.',
                    ]);
                });
        });
    }
}
