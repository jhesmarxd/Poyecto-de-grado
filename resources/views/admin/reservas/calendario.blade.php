@extends('layouts.app')

@section('title', 'Calendario de reservas')

@section('content')
    <div class="hero">
        <div class="hero-text">
            <span class="eyebrow">Reservas y agenda</span>
            <h1 class="hero-title">Calendario de <span class="accent">reservas</span></h1>
            <p class="hero-sub">Consulta la programación del complejo y el estado operativo de cada reserva.</p>
        </div>
    </div>


    @include('reservas.partials.calendar', [
        'calendarRole' => 'administrador',
        'eventsUrl' => route('admin.reservas.eventos'),
        'canchas' => $canchas,
    ])
@endsection
