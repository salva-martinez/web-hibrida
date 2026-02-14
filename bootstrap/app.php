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
            ? (Auth::user()->role === 'fisio' ? route('admin.dashboard') : route('paciente.dashboard'))
            : route('login')
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
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
