@extends('layouts.app')
@section('title', 'Crear cuenta de Fisioterapeuta')

@section('content')
    <div class="login-container">
        <div class="login-card">
            <div class="logo">
                <span>💪</span>
                <h1>FisioApp</h1>
                <p>Has sido invitado/a como fisioterapeuta</p>
            </div>

            <form method="POST" action="{{ route('register.store', $invitacion->token) }}">
                @csrf

                <div class="form-group">
                    <label for="nombre">Nombre</label>
                    <input type="text" name="nombre" id="nombre" class="form-control" value="{{ old('nombre') }}"
                        required autofocus>
                </div>

                <div class="form-group">
                    <label for="apellido1">Primer Apellido</label>
                    <input type="text" name="apellido1" id="apellido1" class="form-control"
                        value="{{ old('apellido1') }}" required>
                </div>

                <div class="form-group">
                    <label for="apellido2">Segundo Apellido</label>
                    <input type="text" name="apellido2" id="apellido2" class="form-control"
                        value="{{ old('apellido2') }}" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" class="form-control"
                        value="{{ old('email', $invitacion->email) }}" required>
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" name="password" id="password" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Confirmar Contraseña</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" class="form-control"
                        required>
                </div>

                <button type="submit" class="btn btn-primary">Crear cuenta</button>
            </form>
        </div>
    </div>
@endsection
