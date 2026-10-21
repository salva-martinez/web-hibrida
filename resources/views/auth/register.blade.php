@extends('layouts.app')
@section('title', 'Crear cuenta de fisioterapeuta')

@section('content')
    <div class="login-container">
        <div class="login-card">
            <div class="logo">
                <span>💪</span>
                <h1>Crear cuenta</h1>
                <p>Alta de fisioterapeuta</p>
            </div>

            <p style="color: var(--text-secondary); font-size: .9rem; margin-bottom: 1.5rem; text-align:center">
                Cada fisioterapeuta gestiona su propia cartera de pacientes y su propio catálogo
                de ejercicios. Ningún profesional ve los datos de otro.
            </p>

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <div class="form-group">
                    <label for="nombre">Nombre</label>
                    <input type="text" name="nombre" id="nombre" class="form-control" placeholder="Tu nombre"
                        value="{{ old('nombre') }}" required>
                </div>

                <div class="form-group">
                    <label for="apellido1">Primer Apellido</label>
                    <input type="text" name="apellido1" id="apellido1" class="form-control" placeholder="Primer apellido"
                        value="{{ old('apellido1') }}" required>
                </div>

                <div class="form-group">
                    <label for="apellido2">Segundo Apellido</label>
                    <input type="text" name="apellido2" id="apellido2" class="form-control" placeholder="Segundo apellido"
                        value="{{ old('apellido2') }}" required>
                </div>

                <div class="form-group">
                    <label for="email">Email profesional</label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="tu@clinica.com"
                        value="{{ old('email') }}" required>
                    @error('email')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                    <small style="color: var(--text-muted);">Recibirás aquí el enlace para activar la cuenta.</small>
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Mínimo 8 caracteres"
                        required>
                    @error('password')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Repite la contraseña</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" class="form-control"
                        placeholder="••••••••" required>
                    @error('password_confirmation')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%">Crear mi cuenta</button>

                <p style="text-align:center; margin-top: 1rem; font-size: .85rem; color: var(--text-secondary)">
                    ¿Ya tienes cuenta?
                    <a href="{{ route('login') }}" style="color: var(--primary);">Inicia sesión</a>
                </p>
            </form>
        </div>
    </div>
@endsection
