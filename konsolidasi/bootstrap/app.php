<?php

use App\Http\Middleware\isAdmin;
use App\Http\Middleware\isKabkot;
use App\Http\Middleware\isPusat;
use App\Http\Middleware\isProvinsi;
use App\Http\Middleware\isOperator;
use App\Http\Middleware\IsProvinsiOrKabkot;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->appendToGroup('pusat', [
            'auth',
            isPusat::class,
        ]);

        $middleware->appendToGroup('provinsi', [
            'auth',
            isProvinsi::class,
        ]);

        $middleware->appendToGroup('kabkot', [
            'auth',
            isKabkot::class,
        ]);

        $middleware->appendToGroup('admin', [
            'auth',
            isAdmin::class,
        ]);

        $middleware->appendToGroup('operator', [
            'auth',
            isOperator::class,
        ]);

        $middleware->appendToGroup('provinsi_or_kabkot', [
            'auth',
            IsProvinsiOrKabkot::class,
        ]);

        // Exclude /rekonsiliasi/update/* from CSRF protection
        // $middleware->validateCsrfTokens(except: [
        //     '/rekonsiliasi/update/*',
        //     '/rekonsiliasi/pengisian/*',
        //     '/user/*',
        // ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (PostTooLargeException $e, Request $request) {
            Log::error('PostTooLargeException caught!');
            Log::error('Request path: ' . $request->path());
            Log::error('CONTENT_LENGTH: ' . $request->server('CONTENT_LENGTH'));
            Log::error('Max allowed size: ' . ini_get('post_max_size'));
            Log::error('Previous URL: ' . $request->headers->get('referer'));

            // Optional: flash something to session to confirm behavior
            session()->flash('debug_message', 'Exception was triggered and handler ran.');

            return redirect()
                ->route('data.create', ['error' => 'too-big']);
        });
    })
    ->create();
