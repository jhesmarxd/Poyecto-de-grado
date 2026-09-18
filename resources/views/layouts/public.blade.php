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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

    @include('layouts._colores')

    @stack('styles')
</head>
<body>
    @php
        $route = request()->route() ? request()->route()->getName() : '';
    @endphp

    @hasSection('public-header')
        @yield('public-header')
    @else
    <header class="public-topbar">
        <a class="public-brand" href="{{ route('inicio') }}">
            <span class="public-brand-logo">
                <svg viewBox="0 0 36 36" xmlns="http://www.w3.org/2000/svg">
                    <path fill="#ffffff" d="M14.747 9.125c.527-1.426 1.736-2.573 3.317-2.573c1.643 0 2.792 1.085 3.318 2.573l6.077 16.867c.186.496.248.931.248 1.147c0 1.209-.992 2.046-2.139 2.046c-1.303 0-1.954-.682-2.264-1.611l-.931-2.915h-8.62l-.93 2.884c-.31.961-.961 1.642-2.232 1.642c-1.24 0-2.294-.93-2.294-2.17c0-.496.155-.868.217-1.023l6.233-16.867zm.34 11.256h5.891l-2.883-8.992h-.062l-2.946 8.992z"/>
                </svg>
            </span>
            <span class="public-brand-name">{{ config('app.name', 'Wally Reservas') }}</span>
        </a>

        <nav class="public-nav">
            <a class="public-nav-link {{ $route === 'inicio' ? 'is-active' : '' }}" href="{{ route('inicio') }}">Inicio</a>
            <a class="public-nav-link {{ in_array($route, ['canchas.index', 'canchas.show']) ? 'is-active' : '' }}" href="{{ route('canchas.index') }}">Canchas</a>
            <a class="public-nav-link {{ $route === 'disponibilidad.index' ? 'is-active' : '' }}" href="{{ route('disponibilidad.index') }}">Disponibilidad</a>
        </nav>

        <div class="public-actions">
            <button class="icon-btn" id="themeToggle" aria-label="Cambiar tema"></button>

            @guest
                @if (Route::has('login'))
                    <a class="btn btn--ghost" href="{{ route('login') }}">Iniciar sesión</a>
                @endif
                @if (Route::has('register'))
                    <a class="btn btn--primary" href="{{ route('register') }}">Registrarse</a>
                @endif
            @else
                <a class="btn btn--primary" href="{{ Auth::user()->esAdministrador() ? route('admin.dashboard') : route('reservas.mis') }}">
                    Mi panel
                </a>
                <a class="btn btn--ghost" href="{{ route('logout') }}"
                   onclick="event.preventDefault(); document.getElementById('logout-form-public').submit();">
                    Cerrar sesión
                </a>
                <form id="logout-form-public" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
            @endguest
        </div>
    </header>
    @endif

    <main class="public-main">
        @if (session('status'))
            <div data-crud-status="{{ session('status') }}" class="d-none" aria-hidden="true"></div>
        @endif

        @yield('content')
    </main>

    <footer class="public-footer">
        <div>{{ config('app.name', 'Wally Reservas') }} — Reservas y atención automatizada para canchas</div>
        <div class="public-footer-meta">
            <span>© {{ date('Y') }}</span>
        </div>
    </footer>

    <div id="balones-update-banner" class="d-none"
         style="position: fixed; bottom: 1.25rem; left: 50%; transform: translateX(-50%); z-index: 1080;
                max-width: 92vw;">
        <div class="alert alert-warning shadow-lg d-flex align-items-center gap-2 mb-0 rounded-4">
            <span>🔄 Se actualizó un cambio en los balones. Espera por favor y recarga nuevamente.</span>
            <button type="button" class="btn btn-sm btn-warning text-dark fw-bold" onclick="location.reload()">Recargar</button>
            <button type="button" class="btn-close" data-dismiss-balon-banner aria-label="Cerrar"
                    onclick="this.closest('#balones-update-banner').classList.add('d-none')"></button>
        </div>
    </div>

    @include('chatbot.widget')

    <script>
        (function () {
            var versionActual = @json(\App\Models\Balon::version());
            var intervalo = 8000;

            function actualizarTarjetas(datos) {
                var grid = document.getElementById('balones-grid');
                if (!grid) return;

                grid.dataset.version = datos.version;

                var porId = {};
                (datos.balones || []).forEach(function (b) { porId[b.id] = b; });

                document.querySelectorAll('#balones-grid .balon-precio').forEach(function (el) {
                    var b = porId[parseInt(el.dataset.id, 10)];
                    if (b) {
                        el.textContent = 'Bs. ' + Number(b.precio_por_hora).toFixed(2) + ' / h';
                        el.dataset.precio = b.precio_por_hora;
                    }
                });

                document.querySelectorAll('#balones-grid .balon-img').forEach(function (img) {
                    var b = porId[parseInt(img.dataset.id, 10)];
                    var base = img.dataset.imagen;
                    if (b && base) {
                        img.src = base + (base.indexOf('?') >= 0 ? '&' : '?') + 'v=' + encodeURIComponent(datos.version);
                    }
                });

                if (typeof window.actualizarTotalReserva === 'function') {
                    window.actualizarTotalReserva();
                }
            }

            function revisar() {
                fetch(@json(route('balones.version')), { cache: 'no-store' })
                    .then(function (r) { return r.json(); })
                    .then(function (datos) {
                        if (datos.version && datos.version !== versionActual) {
                            versionActual = datos.version;
                            actualizarTarjetas(datos);
                            document.getElementById('balones-update-banner').classList.remove('d-none');
                        }
                    })
                    .catch(function () {});
            }

            setInterval(revisar, intervalo);
        })();
    </script>

    @stack('scripts')
</body>
</html>
