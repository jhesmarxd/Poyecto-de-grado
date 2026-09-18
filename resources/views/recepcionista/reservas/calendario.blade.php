@extends('layouts.app')

@section('title', 'Calendario operativo')

@section('content')
    <div class="hero">
        <div class="hero-text">
            <span class="eyebrow">Agenda operativa</span>
            <h1 class="hero-title">Calendario <span class="accent">operativo</span></h1>
            <p class="hero-sub">Consulta horarios, clientes y recursos asignados sin abandonar el flujo de recepción.</p>
        </div>
    </div>

    @include('recepcionista._navigation')

    @include('reservas.partials.calendar', [
        'calendarRole' => 'recepcionista',
        'eventsUrl' => route('recepcionista.reservas.eventos'),
        'canchas' => $canchas,
    ])
@endsection
