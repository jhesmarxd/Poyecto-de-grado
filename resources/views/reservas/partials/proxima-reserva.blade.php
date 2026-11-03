@php
    $inicioReserva = $reserva->iniciaEn();
    $finReserva = $reserva->finalizaEn();
@endphp

<section class="reservation-countdown card"
         data-reservation-countdown
         data-start-at="{{ $inicioReserva->getTimestamp() * 1000 }}"
         data-end-at="{{ $finReserva->getTimestamp() * 1000 }}"
         data-server-now="{{ $ahoraServidor->getTimestamp() * 1000 }}"
         aria-labelledby="reservation-countdown-title">
    <div class="reservation-countdown__identity">
        <div class="reservation-countdown__icon" aria-hidden="true">
            <img src="{{ asset('img/iconos/reloj tiempo icono .gif') }}" alt=""
                 data-countdown-animation
                 data-upcoming-src="{{ asset('img/iconos/reloj tiempo icono .gif') }}"
                 data-active-src="{{ asset('img/iconos/animacion de carga balon voley .gif') }}">
        </div>
        <div class="reservation-countdown__court-image">
            @if ($reserva->cancha->imagenUrl())
                <img src="{{ $reserva->cancha->imagenUrl() }}" alt="{{ $reserva->cancha->nombre }}" loading="lazy">
            @else
                <i class="bi bi-grid-3x2" aria-hidden="true"></i>
            @endif
        </div>
        <div class="reservation-countdown__details">
            <span class="reservation-countdown__status" data-countdown-status>Reserva próxima</span>
            <h2 id="reservation-countdown-title">{{ $reserva->cancha->nombre }}</h2>
            <p>
                {{ $inicioReserva->isToday() ? 'Hoy' : $inicioReserva->translatedFormat('D, d M') }}
                <span aria-hidden="true">·</span>
                {{ $inicioReserva->format('H:i') }}–{{ $finReserva->format('H:i') }}
            </p>
        </div>
    </div>

    <div class="reservation-countdown__timer">
        <span data-countdown-caption>Comienza en</span>
        <div class="reservation-countdown__units" role="timer" aria-live="off" aria-label="Tiempo restante">
            <div class="reservation-countdown__unit"><strong data-countdown-days>00</strong><small>días</small></div>
            <div class="reservation-countdown__unit"><strong data-countdown-hours>00</strong><small>horas</small></div>
            <div class="reservation-countdown__unit"><strong data-countdown-minutes>00</strong><small>min</small></div>
            <div class="reservation-countdown__unit" data-countdown-seconds-unit hidden><strong data-countdown-seconds>00</strong><small>seg</small></div>
        </div>
        <small>Hora sincronizada con el sistema</small>
    </div>
</section>
