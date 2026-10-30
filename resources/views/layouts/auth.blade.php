<!doctype html>
<html lang="es" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Wally Reservas'))</title>

    <script>
        (function () {
            try {
                var saved = localStorage.getItem('wally-theme');
                var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                document.documentElement.setAttribute('data-theme', saved || (prefersDark ? 'dark' : 'light'));
            } catch (e) {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;family=Inter+Tight:wght@500;600;700&amp;family=JetBrains+Mono:wght@400;500&amp;display=swap">

    @vite(['resources/sass/auth.scss'])

    @include('layouts._colores')

    @stack('styles')
</head>
<body>
    @php($authCentered = $__env->hasSection('auth-centered'))

    <div class="auth-shell {{ $authCentered ? 'auth-shell--centered' : '' }}">
        @unless ($authCentered)
        {{-- Panel deportivo de marca (derecha en desktop vía order en _auth/_responsive) --}}
        <aside class="auth-aside" aria-label="Bienvenida a Wally Reservas">
            <div class="auth-brand">
                <div class="logo">
                    <svg viewBox="0 0 36 36" xmlns="http://www.w3.org/2000/svg">
                        <path fill="#ffffff" d="M14.747 9.125c.527-1.426 1.736-2.573 3.317-2.573c1.643 0 2.792 1.085 3.318 2.573l6.077 16.867c.186.496.248.931.248 1.147c0 1.209-.992 2.046-2.139 2.046c-1.303 0-1.954-.682-2.264-1.611l-.931-2.915h-8.62l-.93 2.884c-.31.961-.961 1.642-2.232 1.642c-1.24 0-2.294-.93-2.294-2.17c0-.496.155-.868.217-1.023l6.233-16.867zm.34 11.256h5.891l-2.883-8.992h-.062l-2.946 8.992z"/>
                    </svg>
                </div>
                <div class="name">{{ config('app.name', 'Wally Reservas') }}</div>
            </div>

            <div class="auth-aside-body">
                <span class="auth-aside-eyebrow">Juega · Reserva · Disfruta</span>
                <h1>Tu próxima jugada empieza aquí.</h1>
                <p>Encuentra una cancha, elige tu horario y mantén cada reserva bajo control desde una experiencia rápida y clara.</p>

                <div class="auth-sport-points" aria-label="Ventajas de la plataforma">
                    <span class="auth-sport-point">Disponibilidad real</span>
                    <span class="auth-sport-point">Reserva en segundos</span>
                    <span class="auth-sport-point">Agenda en vivo</span>
                </div>
            </div>

            <div class="auth-aside-footer">
                <span>Wally · Entra a la cancha</span>
                <span>© {{ date('Y') }}</span>
            </div>
        </aside>
        @endunless

        {{-- Panel de formulario --}}
        <main class="auth-main">
            <div class="auth-main-top">
                <a class="brand-back" href="{{ route('inicio') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m12 19-7-7 7-7M19 12H5"/></svg>
                    Volver al inicio
                </a>
                <button class="icon-btn" id="themeToggle" aria-label="Cambiar tema"></button>
            </div>

            <div class="auth-card">
                @if (session('status'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('status') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                    </div>
                @endif

                @yield('content')
            </div>

            <div class="auth-main-bottom">
                <a href="{{ route('inicio') }}">{{ config('app.name', 'Wally Reservas') }}</a> — Reservas y atención automatizada para canchas
            </div>
        </main>
    </div>

    @vite(['resources/js/auth.js'])

    @stack('scripts')
</body>
</html>
