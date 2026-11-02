<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Ohne Web-Middleware (keine Session/Cookies), damit sie auch vor dem ersten APP_KEY funktionieren.
            \Illuminate\Support\Facades\Route::post('/_deploy', [\App\Http\Controllers\DeployHookController::class, 'deploy']);
            \Illuminate\Support\Facades\Route::get('/_cron', [\App\Http\Controllers\DeployHookController::class, 'schedule']);
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo('/');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
