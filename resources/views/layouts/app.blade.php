<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'FisioApp') — FisioApp</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>

<body>
    {{-- Navigation --}}
    <nav class="navbar">
        <div class="navbar-container">
            <a href="{{ auth()->check() ? (auth()->user()->isFisio() ? route('admin.dashboard') : route('paciente.dashboard')) : route('login') }}"
                class="navbar-brand">
                <span class="brand-icon">💪</span>
                <span class="brand-text">FisioApp</span>
            </a>

            @auth
                <div class="navbar-menu">
                    @if(auth()->user()->isFisio())
                        <a href="{{ route('admin.dashboard') }}"
                            class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>
                        <a href="{{ route('admin.pacientes.index') }}"
                            class="nav-link {{ request()->routeIs('admin.pacientes.*') ? 'active' : '' }}">Pacientes</a>
                        <a href="{{ route('admin.estimulos.index') }}"
                            class="nav-link {{ request()->routeIs('admin.estimulos.*') ? 'active' : '' }}">Estímulos</a>
                        <a href="{{ route('admin.ejercicios.index') }}"
                            class="nav-link {{ request()->routeIs('admin.ejercicios.*') ? 'active' : '' }}">Ejercicios</a>
                        <a href="{{ route('admin.planes.index') }}"
                            class="nav-link {{ request()->routeIs('admin.planes.*') ? 'active' : '' }}">Planes</a>
                    @endif
                </div>

                <div class="navbar-user">
                    <span class="user-name">{{ auth()->user()->nombre_completo }}</span>
                    <span
                        class="user-role {{ auth()->user()->role }}">{{ auth()->user()->isFisio() ? '🩺 Fisio' : '🏃 Paciente' }}</span>
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="btn btn-logout">Salir</button>
                    </form>
                </div>
            @endauth
        </div>
    </nav>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success">
            <span>✅</span> {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-error">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Main Content --}}
    <main class="main-content">
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="footer">
        <p>&copy; {{ date('Y') }} FisioApp — Gestión de pautas de rehabilitación</p>
    </footer>

    @stack('scripts')
</body>

</html>