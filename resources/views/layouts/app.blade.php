<!doctype html>
<html lang="es" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Wally Reservas'))</title>

    <script>
        // Tema temprano: fija data-theme antes de que llegue el CSS para
        // que los usuarios en modo oscuro no vean un flash claro.
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

    <!-- Fuentes (no @import en SCSS: la petición de fuente arranca en paralelo) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;family=Inter+Tight:wght@500;600;700&amp;family=JetBrains+Mono:wght@400;500&amp;display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Scripts y estilos -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

    @include('layouts._colores')

    @stack('styles')
</head>
<body data-active="{{ $__active ?? 'dashboard' }}">
    <div class="shell">
        {{-- ============ SIDEBAR ============ --}}
        <aside class="d-sidebar">
            <div class="brand">
                <div class="brand-logo">
                    <svg viewBox="0 0 36 36" xmlns="http://www.w3.org/2000/svg">
                        <path fill="#ffffff" d="M14.747 9.125c.527-1.426 1.736-2.573 3.317-2.573c1.643 0 2.792 1.085 3.318 2.573l6.077 16.867c.186.496.248.931.248 1.147c0 1.209-.992 2.046-2.139 2.046c-1.303 0-1.954-.682-2.264-1.611l-.931-2.915h-8.62l-.93 2.884c-.31.961-.961 1.642-2.232 1.642c-1.24 0-2.294-.93-2.294-2.17c0-.496.155-.868.217-1.023l6.233-16.867zm.34 11.256h5.891l-2.883-8.992h-.062l-2.946 8.992z"/>
                    </svg>
                </div>
                <div class="brand-text">
                    <div class="brand-name">{{ config('app.name', 'Wally Reservas') }}</div>
                    <div class="brand-tag">reservas · chatbot</div>
                </div>
            </div>

            @php
                $route = request()->route() ? request()->route()->getName() : '';
            @endphp

            @include('layouts._sidebar_navigation', ['route' => $route])


            <div class="sidebar-footer">
                @auth
                <div class="workspace">
                    <div class="workspace-avatar">{{ strtoupper(substr(Auth::user()->name, 0, 2)) }}</div>
                    <div class="workspace-text">
                        <div class="workspace-name">{{ Auth::user()->name }}</div>
                        <div class="workspace-role">{{ \App\Models\User::ROLES[Auth::user()->rol] ?? ucfirst(Auth::user()->rol) }}</div>
                    </div>
                </div>
                @else
                <div class="workspace">
                    <div class="workspace-text">
                        <div class="workspace-name">Invitado</div>
                        <div class="workspace-role">explorador</div>
                    </div>
                </div>
                @endauth
            </div>
        </aside>

        {{-- ============ MAIN ============ --}}
        <div class="main">
            <header class="d-topbar">
                <div class="crumbs">
                    <button class="hamburger" data-drawer-open aria-label="Abrir navegación">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                    </button>
                    <span>{{ config('app.name', 'Wally Reservas') }}</span>
                    <svg class="sep" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                    <span class="current">@yield('title', 'Panel')</span>
                </div>

                <div class="topbar-actions">
                    @auth
                        @if (Auth::user()->rol === \App\Models\User::ROL_CLIENTE)
                            @include('layouts._notifications_dropdown')
                        @endif
                    @endauth

                    <button class="icon-btn" id="themeToggle" aria-label="Cambiar tema"></button>

                    @auth
                    <div class="dd-wrap">
                        <div class="avatar" data-dropdown tabindex="0" role="button" aria-label="Menú de cuenta">{{ strtoupper(substr(Auth::user()->name, 0, 2)) }}</div>
                        <div class="dd-menu dd-profile" role="menu">
                            <div class="dd-profile-head">
                                <div class="dd-profile-name">{{ Auth::user()->name }}</div>
                                <div class="dd-profile-email">{{ Auth::user()->email }}</div>
                            </div>
                            <a class="dd-menu-item" href="{{ Auth::user()->esAdministrador() ? route('admin.dashboard') : (Auth::user()->esRecepcionista() ? route('recepcionista.reservas.hoy') : route('reservas.mis')) }}">
                                <svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg>
                                {{ Auth::user()->esAdministrador() ? 'Panel operativo' : (Auth::user()->esRecepcionista() ? 'Agenda operativa' : 'Mis reservas') }}
                            </a>
                            <a class="dd-menu-item" href="{{ route('perfil.edit') }}">
                                <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                Mi perfil
                            </a>
                            <div class="dd-divider"></div>
                            <a class="dd-menu-item danger" href="{{ route('logout') }}"
                               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                                Cerrar sesión
                            </a>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                @csrf
                            </form>
                        </div>
                    </div>
                    @else
                    <a class="btn btn--ghost" href="{{ route('login') }}">Iniciar sesión</a>
                    <a class="btn btn--primary" href="{{ route('register') }}">Registrarse</a>
                    @endauth
                </div>
            </header>

            <main class="content">
                @if (session('status'))
                    <div data-crud-status="{{ session('status') }}" class="d-none" aria-hidden="true"></div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                    </div>
                @endif

                @yield('content')
            </main>

            <footer class="d-footer">
                <div>© {{ date('Y') }} · {{ config('app.name', 'Wally Reservas') }}</div>
                <div class="d-footer-meta">
                    <span>Sistema de reservas</span>
                    <span>Laravel + Bootstrap</span>
                </div>
            </footer>
        </div>
    </div>

    @include('chatbot.widget')

    @stack('scripts')
</body>
</html>
