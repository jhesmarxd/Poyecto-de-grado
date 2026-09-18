@extends('layouts.app')

@section('title', 'Check-In')

@section('content')
@include('recepcionista._navigation')

    <div class="hero">
        <div class="hero-text">
            <span class="eyebrow">Recepcionista · Check-In</span>
            <h1 class="hero-title"><span class="accent">Check-In</span> de clientes</h1>
            <p class="hero-sub">Registrar llegada de clientes. Fecha: {{ $fecha }}</p>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-scroll">
                <table class="table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th>N.º</th>
                            <th>Cliente</th>
                            <th>Cancha</th>
                            <th>Horario</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reservas as $reserva)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $reserva->usuario->name }}</td>
                                <td>{{ $reserva->cancha->nombre }}</td>
                                <td>{{ $reserva->iniciaEn()->format('H:i') }} - {{ $reserva->finalizaEn()->format('H:i') }}</td>
                                <td>
                                    <form method="POST" action="{{ route('recepcionista.checkin.store', $reserva) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">✓ Check-In</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">No hay reservas pendientes de check-in para hoy.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
