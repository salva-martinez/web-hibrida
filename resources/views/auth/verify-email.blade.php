@extends('layouts.app')
@section('title', 'Verifica tu email')

@section('content')
    <div class="login-container">
        <div class="login-card">
            <div class="logo">
                <span>📧</span>
                <h1>Verifica tu email</h1>
                <p>Un paso más para empezar</p>
            </div>

            <p style="color: var(--text-secondary); font-size: .9rem; text-align:center; margin-bottom: 1.5rem">
                Hemos enviado un enlace de verificación a
                <strong style="color: var(--text-primary)">{{ auth()->user()->email }}</strong>.
                Ábrelo para activar tu cuenta y acceder al panel.
            </p>

            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="btn btn-primary" style="width:100%">Reenviar email de verificación</button>
            </form>

            <form method="POST" action="{{ route('logout') }}" style="margin-top: .75rem">
                @csrf
                <button type="submit" class="btn btn-secondary" style="width:100%">Salir</button>
            </form>

            <p style="text-align:center; margin-top: 1rem; font-size: .8rem; color: var(--text-muted)">
                ¿No llega el correo? Revisa la carpeta de spam. En desarrollo puedes encontrar
                el enlace en <code>storage/logs/laravel.log</code>.
            </p>
        </div>
    </div>
@endsection
