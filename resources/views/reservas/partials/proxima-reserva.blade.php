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
        <div>
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
        <strong data-countdown-value role="timer" aria-live="off">--:--:--</strong>
        <small>Hora sincronizada con el sistema</small>
    </div>
</section>
