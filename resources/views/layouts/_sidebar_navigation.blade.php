@auth
    @if (Auth::user()->esAdministrador())
        @php
            $reservasActivo = str_starts_with($route, 'admin.reservas.');
            $recursosActivo = str_starts_with($route, 'admin.canchas.') || str_starts_with($route, 'admin.horarios.') || str_starts_with($route, 'admin.balones.');
            $analisisActivo = str_starts_with($route, 'admin.reportes.') || str_starts_with($route, 'admin.chatbot.');
            $configuracionActiva = str_starts_with($route, 'admin.configuracion-reservas.');
        @endphp
        <nav class="nav-section" aria-label="Administración">
            <div class="nav-label">Administración</div>
            <a class="nav-link {{ $route === 'admin.dashboard' ? 'is-active' : '' }}" href="{{ route('admin.dashboard') }}">
                <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>
                <span>Dashboard</span>
            </a>

            <div class="nav-item-group {{ $reservasActivo ? 'is-open' : '' }}" data-nav-group>
                <button type="button" class="nav-link {{ $reservasActivo ? 'is-active' : '' }}" data-nav-toggle
                        aria-expanded="{{ $reservasActivo ? 'true' : 'false' }}" aria-controls="nav-reservas">
                    <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    <span>Reservas y agenda</span>
                    <svg class="chev" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
                </button>
                <div class="nav-submenu" id="nav-reservas">
                    <a class="{{ $route === 'admin.reservas.calendario' ? 'is-active' : '' }}" href="{{ route('admin.reservas.calendario') }}">Calendario de reservas</a>
                    <a class="{{ $route === 'admin.reservas.index' ? 'is-active' : '' }}" href="{{ route('admin.reservas.index') }}">Gestión de reservas</a>
                </div>
            </div>

            <div class="nav-item-group {{ $recursosActivo ? 'is-open' : '' }}" data-nav-group>
                <button type="button" class="nav-link {{ $recursosActivo ? 'is-active' : '' }}" data-nav-toggle
                        aria-expanded="{{ $recursosActivo ? 'true' : 'false' }}" aria-controls="nav-recursos">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 6.5 16.8 9.2 15.4 14.6H8.6L7.2 9.2 12 6.5Z"/></svg>
                    <span>Recursos deportivos</span>
                    <svg class="chev" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
                </button>
                <div class="nav-submenu" id="nav-recursos">
                    <a class="{{ str_starts_with($route, 'admin.canchas.') ? 'is-active' : '' }}" href="{{ route('admin.canchas.index') }}">Canchas</a>
                    <a class="{{ str_starts_with($route, 'admin.horarios.') ? 'is-active' : '' }}" href="{{ route('admin.horarios.index') }}">Franjas reservables</a>
                    <a class="{{ str_starts_with($route, 'admin.balones.') ? 'is-active' : '' }}" href="{{ route('admin.balones.index') }}">Balones</a>
                </div>
            </div>

            <a class="nav-link {{ str_starts_with($route, 'admin.usuarios.') ? 'is-active' : '' }}" href="{{ route('admin.usuarios.index') }}">
                <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg>
                <span>Usuarios y accesos</span>
            </a>

            <div class="nav-item-group {{ $analisisActivo ? 'is-open' : '' }}" data-nav-group>
                <button type="button" class="nav-link {{ $analisisActivo ? 'is-active' : '' }}" data-nav-toggle
                        aria-expanded="{{ $analisisActivo ? 'true' : 'false' }}" aria-controls="nav-analisis">
                    <svg viewBox="0 0 24 24"><path d="M3 20V4M7 20v-6M11 20v-10M15 20v-4M19 20V8"/></svg>
                    <span>Inteligencia y reportes</span>
                    <svg class="chev" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
                </button>
                <div class="nav-submenu" id="nav-analisis">
                    <a class="{{ str_starts_with($route, 'admin.reportes.') ? 'is-active' : '' }}" href="{{ route('admin.reportes.index') }}">Reportes operativos</a>
                    <a class="{{ $route === 'admin.chatbot.metricas' ? 'is-active' : '' }}" href="{{ route('admin.chatbot.metricas') }}">Métricas del chatbot</a>
                    <a class="{{ str_starts_with($route, 'admin.chatbot.conversaciones') ? 'is-active' : '' }}" href="{{ route('admin.chatbot.conversaciones') }}">Conversaciones del chatbot</a>
                </div>
            </div>

            <div class="nav-item-group {{ $configuracionActiva ? 'is-open' : '' }}" data-nav-group>
                <button type="button" class="nav-link {{ $configuracionActiva ? 'is-active' : '' }}" data-nav-toggle
                        aria-expanded="{{ $configuracionActiva ? 'true' : 'false' }}" aria-controls="nav-configuracion">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33M4.6 9a1.65 1.65 0 0 0-.33-1.82"/></svg>
                    <span>Configuración e integraciones</span>
                    <svg class="chev" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
                </button>
                <div class="nav-submenu" id="nav-configuracion">
                    <a class="{{ str_starts_with($route, 'admin.configuracion-reservas.') ? 'is-active' : '' }}" href="{{ route('admin.configuracion-reservas.index') }}">Reglas de reservas y chatbot</a>
                </div>
            </div>
        </nav>
    @elseif (Auth::user()->esRecepcionista())
        <nav class="nav-section" aria-label="Recepción">
            <div class="nav-label">Recepción</div>
            <a class="nav-link {{ $route === 'recepcionista.reservas.hoy' ? 'is-active' : '' }}" href="{{ route('recepcionista.reservas.hoy') }}">
                <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                <span>Agenda operativa</span>
            </a>
            <a class="nav-link {{ $route === 'recepcionista.reservas.calendario' ? 'is-active' : '' }}" href="{{ route('recepcionista.reservas.calendario') }}">
                <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M3 10h18M8 2v4M16 2v4M8 15h3M14 15h3"/></svg>
                <span>Calendario operativo</span>
            </a>
            <a class="nav-link {{ $route === 'recepcionista.reservas.create' ? 'is-active' : '' }}" href="{{ route('recepcionista.reservas.create') }}">
                <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                <span>Registrar reserva</span>
            </a>
            <a class="nav-link {{ str_starts_with($route, 'recepcionista.clientes.') ? 'is-active' : '' }}" href="{{ route('recepcionista.clientes.buscar') }}">
                <svg viewBox="0 0 24 24"><circle cx="11" cy="8" r="4"/><path d="M4 21v-2a6 6 0 0 1 12 0v2M19 8v6M16 11h6"/></svg>
                <span>Clientes y atención</span>
            </a>
        </nav>
    @else
        @include('layouts._client_navigation', ['route' => $route])
    @endif
@else
    <nav class="nav-section" aria-label="Principal">
        <div class="nav-label">Principal</div>
        <a class="nav-link {{ $route === 'inicio' ? 'is-active' : '' }}" href="{{ route('inicio') }}">
            <svg viewBox="0 0 24 24"><path d="M3 12 12 3l9 9"/><path d="M5 10v10h14V10"/></svg><span>Inicio</span>
        </a>
        <a class="nav-link {{ str_starts_with($route, 'canchas.') ? 'is-active' : '' }}" href="{{ route('canchas.index') }}">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 6.5 16.8 9.2 15.4 14.6H8.6L7.2 9.2 12 6.5Z"/></svg><span>Canchas</span>
        </a>
        <a class="nav-link {{ $route === 'disponibilidad.index' ? 'is-active' : '' }}" href="{{ route('disponibilidad.index') }}">
            <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg><span>Disponibilidad</span>
        </a>
    </nav>
@endauth
