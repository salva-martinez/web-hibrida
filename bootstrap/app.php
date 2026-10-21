<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // La aplicación se sirve detrás de nginx (reverse proxy). Sin confiar en
        // los proxies, Laravel cree que la petición es HTTP y genere URLs con
        // http:// (el enlace de verificación de email y las cookies seguras
        // fallarían). Se confían solo las redes privadas y el loopback: el
        // bridge de Docker y el propio nginx local.
        $middleware->trustProxies(at: [
            '127.0.0.1',
            '::1',
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
        ]);

        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
        ]);
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => Auth::check()
            ? (Auth::user()->role === 'fisio'
                ? (Auth::user()->hasVerifiedEmail() ? route('admin.dashboard') : route('verification.notice'))
                : route('paciente.dashboard'))
            : route('login')
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Un enlace de verificación caducado o manipulado no debe dejar al usuario
        // en un 403 genérico: se le devuelve a la pantalla de verificación.
        $exceptions->render(function (\Illuminate\Routing\Exceptions\InvalidSignatureException $e, Request $request) {
            if ($request->routeIs('verification.verify')) {
                return redirect()->route('verification.notice')
                    ->with('error', 'El enlace de verificación no es válido o ha caducado. Pide uno nuevo.');
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, Request $request) {
            if ($e->getStatusCode() === 403) {
                // Las peticiones AJAX (el polling del análisis de IA) necesitan
                // un 403 en JSON, no una redirección a una página HTML.
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'No tienes permisos para acceder a esta sección.'], 403);
                }

                if (Auth::check()) {
                    $route = Auth::user()->role === 'fisio' ? 'admin.dashboard' : 'paciente.dashboard';
                    return redirect()->route($route)->with('error', 'No tienes permisos para acceder a esta sección.');
                }
                return redirect()->route('login')->with('error', 'Inicia sesión para acceder.');
            }
        });
    })->create();
