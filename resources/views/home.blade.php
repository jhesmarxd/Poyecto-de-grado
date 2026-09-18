@extends('layouts.app')

@section('title', 'Mi panel')

@section('content')
    <div class="hero">
        <div class="hero-text">
            <span class="eyebrow">Mi panel</span>
            <h1 class="hero-title">¡Hola, <span class="accent">{{ Auth::user()->name }}</span>!</h1>
            <p class="hero-sub">Bienvenido al sistema de reservas de canchas de Wally.</p>
        </div>
        <div class="hero-actions">
            <a href="{{ route('disponibilidad.index') }}" class="btn btn--primary">Consultar disponibilidad</a>
            <a href="{{ route('reservas.mis') }}" class="btn btn--ghost">Mis reservas</a>
        </div>
    </div>

    <div class="grid">
        <div class="card col-8">
            <div class="card-head">
                <div class="card-title-wrap">
                    <span class="eyebrow">Estado del sistema</span>
                    <h3 class="card-title">Bienvenido</h3>
                </div>
            </div>
            <div class="card-body pt-0">
                <p class="mb-0">
                    Reserva tu cancha favorita, consulta la disponibilidad en tiempo real y recibe
                    atención con nuestro asistente virtual.
                </p>
            </div>
        </div>
    </div>
@endsection
