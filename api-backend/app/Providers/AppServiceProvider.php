<?php

namespace App\Providers;

use App\Notifications\Channels\FirebaseChannel;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Notifications\ChannelManager;
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
        $this->app->make(ChannelManager::class)->extend('firebase', function ($app) {
            return $app->make(FirebaseChannel::class);
        });

        // ── Rate Limiters ──────────────────────────────────────
        RateLimiter::for('api', function (mixed $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('login', function (mixed $request) {
            return Limit::perMinute(5)->by($request->input('email') ?: $request->ip());
        });

        RateLimiter::for('register', function (mixed $request) {
            return Limit::perMinute(3)->by($request->ip());
        });

        RateLimiter::for('otp', function (mixed $request) {
            return Limit::perMinute(3)->by($request->input('email') ?: $request->ip());
        });

        RateLimiter::for('password-reset', function (mixed $request) {
            return Limit::perMinute(3)->by($request->input('email') ?: $request->ip());
        });

        RateLimiter::for('diagnosis', function (mixed $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('payment', function (mixed $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });
    }
}
