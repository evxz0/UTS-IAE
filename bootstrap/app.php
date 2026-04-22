<?php

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
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);

        // Tambahkan header ngrok-skip-browser-warning ke semua response.
        // Ini memastikan CSS/JS bisa dimuat lewat ngrok tanpa diblokir interstitial.
        $middleware->append(\App\Http\Middleware\NgrokSkipWarning::class);

        // Exclude Midtrans webhook dari CSRF verification.
        // Request ini berasal dari server Midtrans, bukan browser, sehingga
        // tidak memiliki CSRF token dan akan selalu ditolak jika tidak diexclude.
        $middleware->validateCsrfTokens(except: [
            'midtrans/notification',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
