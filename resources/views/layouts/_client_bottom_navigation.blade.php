@php
    $panelActivo = $route === 'home';
    $reservarActivo = in_array($route, ['disponibilidad.index', 'canchas.index', 'canchas.show', 'reservas.crear'], true);
    $reservasActivo = in_array($route, ['reservas.mis', 'reservas.mis.eventos'], true);
@endphp

<nav class="client-bottom-nav" aria-label="Navegación principal">
    <a class="client-bottom-nav__item {{ $panelActivo ? 'is-active' : '' }}" href="{{ route('home') }}"
       @if($panelActivo) aria-current="page" @endif>
        <i class="bi bi-house-door" aria-hidden="true"></i><span>Mi panel</span>
    </a>
    <a class="client-bottom-nav__item {{ $reservarActivo ? 'is-active' : '' }}" href="{{ route('disponibilidad.index') }}"
       @if($reservarActivo) aria-current="page" @endif>
        <i class="bi bi-calendar-plus" aria-hidden="true"></i><span>Reservar</span>
    </a>
    <a class="client-bottom-nav__item {{ $reservasActivo ? 'is-active' : '' }}" href="{{ route('reservas.mis') }}"
       @if($reservasActivo) aria-current="page" @endif>
        <i class="bi bi-calendar2-check" aria-hidden="true"></i><span>Mis reservas</span>
    </a>
    <button type="button" class="client-bottom-nav__item client-bottom-nav__item--assistant" data-chatbot-open
            aria-controls="chatbot-ventana" aria-expanded="false">
        <i class="bi bi-headset" aria-hidden="true"></i><span>Asistente</span>
    </button>
</nav>
