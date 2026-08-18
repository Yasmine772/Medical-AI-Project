<?php

use App\Http\Middleware\CheckPermission;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use \Illuminate\Auth\AuthenticationException;
use App\Http\Middleware\CheckRole;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'permission' => CheckPermission::class,
            'role' =>    CheckRole::class,
        ]);

        $middleware->api(prepend: [
            \Illuminate\Session\Middleware\StartSession::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'admin/*',
            'v1/stripe/webhook',
            'doctor/*'
        ]);

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
    })
    ->withExceptions(function (Exceptions $exceptions): void {
    $exceptions->render(function (AuthenticationException $e, $request) {
        if ($request->is('api/*')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated or Invalid Token',
            ], 401);
        }
    });
    })->create();
