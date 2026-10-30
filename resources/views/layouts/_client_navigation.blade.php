@php
    $panelActivo = $route === 'home';
    $reservarActivo = in_array($route, ['disponibilidad.index', 'canchas.index', 'canchas.show', 'reservas.crear'], true);
    $reservasActivo = in_array($route, ['reservas.mis', 'reservas.mis.eventos'], true);
@endphp

<nav class="nav-section client-primary-nav" aria-label="Navegación del cliente">
    <div class="nav-label">Mi cuenta</div>
    <a class="nav-link {{ $panelActivo ? 'is-active' : '' }}" href="{{ route('home') }}"
       @if($panelActivo) aria-current="page" @endif data-nav-label="Mi panel">
        <i class="bi bi-house-door" aria-hidden="true"></i>
        <span><strong>Mi panel</strong><small>Inicio y accesos rápidos</small></span>
    </a>
    <a class="nav-link {{ $reservarActivo ? 'is-active' : '' }}" href="{{ route('disponibilidad.index') }}"
       @if($reservarActivo) aria-current="page" @endif data-nav-label="Reservar cancha">
        <i class="bi bi-calendar-plus" aria-hidden="true"></i>
        <span><strong>Reservar cancha</strong><small>Consulta horarios libres</small></span>
    </a>
    <a class="nav-link {{ $reservasActivo ? 'is-active' : '' }}" href="{{ route('reservas.mis') }}"
       @if($reservasActivo) aria-current="page" @endif data-nav-label="Mis reservas">
        <i class="bi bi-calendar2-check" aria-hidden="true"></i>
        <span><strong>Mis reservas</strong><small>Revisa y gestiona tus turnos</small></span>
    </a>
    <button type="button" class="nav-link client-assistant-trigger" data-chatbot-open
            data-nav-label="Abrir asistente" aria-controls="chatbot-ventana" aria-expanded="false">
        <i class="bi bi-headset" aria-hidden="true"></i>
        <span><strong>Asistente</strong><small>Recibe ayuda paso a paso</small></span>
    </button>
</nav>
