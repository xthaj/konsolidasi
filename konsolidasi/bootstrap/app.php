<?php

use App\Http\Middleware\isPusat;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Define the 'pusat' middleware group
        $middleware->appendToGroup('pusat', [
            'auth',
            isPusat::class,
        ]);

        // Exclude /rekonsiliasi/update/* from CSRF protection
        $middleware->validateCsrfTokens(except: [
            '/rekonsiliasi/update/*',
            '/rekonsiliasi/progres',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Add custom exception handling if needed
        // Example: Render a custom response for exceptions
        // $exceptions->renderable(function (\Exception $e) {
        //     return response()->json(['error' => $e->getMessage()], 500);
        // });
    })
    ->create();
