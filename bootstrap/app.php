<?php

// Herd workaround:
// Some Herd/Valet configurations can end up instrumenting Laravel's bootstrap
// while the dump interception closure is not defined, resulting in a fatal
// error: "Value of type null is not callable" for $__herd_closure.
//
// Define a safe no-op fallback so the app can boot normally.
if (!is_callable($GLOBALS['__herd_closure'] ?? null)) {
    $__herd_closure = function ($callback = null) {
        return is_callable($callback) ? $callback() : $callback;
    };
    $GLOBALS['__herd_closure'] = $__herd_closure;
} else {
    $__herd_closure = $GLOBALS['__herd_closure'];
}

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
