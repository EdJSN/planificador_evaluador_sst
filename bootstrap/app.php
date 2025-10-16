<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Captura el 419 (TokenMismatch) y redirige al login en navegación normal.
        // Si la petición espera JSON (AJAX/SPA), devuelve un 419 con mensaje.
        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Tu sesión expiró. Vuelve a iniciar sesión.',
                ], 419);
            }

            return redirect()
                ->route('login')
                ->with('status', 'Tu sesión expiró. Vuelve a iniciar sesión.');
        });
    })
    ->create();
