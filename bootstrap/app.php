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
                if (Auth::check()) {
                    $route = Auth::user()->role === 'fisio' ? 'admin.dashboard' : 'paciente.dashboard';
                    return redirect()->route($route)->with('error', 'No tienes permisos para acceder a esta sección.');
                }
                return redirect()->route('login')->with('error', 'Inicia sesión para acceder.');
            }
        });
    })->create();
