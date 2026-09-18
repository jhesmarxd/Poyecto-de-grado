@extends('layouts.app')

@section('title', 'Historial de reservas')

@section('content')
@include('recepcionista._navigation')

    <div class="hero">
        <div class="hero-text">
            <span class="eyebrow">Recepción · Consulta</span>
            <h1 class="hero-title">Historial de <span class="accent">reservas</span></h1>
            <p class="hero-sub">Busca una atención por cliente, cancha, fecha o estado.</p>
        </div>
        <div class="hero-actions">
            <a href="{{ route('recepcionista.reservas.create') }}" class="btn btn--primary">Registrar reserva</a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('recepcionista.reservas.index') }}" class="row g-3">
                <div class="col-lg-4">
                    <label for="q" class="form-label">Cliente o cancha</label>
                    <input type="search" name="q" id="q" class="form-control" value="{{ $termino }}" maxlength="100" placeholder="Nombre, correo, teléfono o cancha">
                </div>
                <div class="col-md-4 col-lg-3">
                    <label for="fecha" class="form-label">Fecha</label>
                    <input type="date" name="fecha" id="fecha" class="form-control" value="{{ $fecha }}">
                </div>
                <div class="col-md-4 col-lg-3">
                    <label for="estado" class="form-label">Estado</label>
                    <select name="estado" id="estado" class="form-select">
                        <option value="">Todos</option>
                        @foreach (\App\Models\Reserva::ESTADOS as $key => $label)
                            <option value="{{ $key }}" {{ $estado === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 col-lg-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn--primary">Filtrar</button>
                    <a href="{{ route('recepcionista.reservas.index') }}" class="btn btn--ghost">Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <div>
                <h2 class="card-title">Resultados</h2>
                <p class="text-muted mb-0">{{ $reservas->total() }} {{ $reservas->total() === 1 ? 'reserva encontrada' : 'reservas encontradas' }}</p>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-scroll">
                <table class="table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th>N.º</th>
                            <th>Cliente</th>
                            <th>Reserva</th>
                            <th>Estado</th>
                            <th>Entrada</th>
                            <th>Salida</th>
                            <th class="text-end">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reservas as $reserva)
                            <tr>
                                <td>{{ $reservas->firstItem() + $loop->index }}</td>
                                <td>
                                    <strong>{{ $reserva->usuario->name }}</strong>
                                    <div class="text-muted small">{{ $reserva->usuario->telefono ?: $reserva->usuario->email }}</div>
                                </td>
                                <td>
                                    <strong>{{ $reserva->cancha->nombre }}</strong>
                                    <div class="text-muted small">
                                        {{ $reserva->fecha->format('d/m/Y') }} ·
                                        {{ $reserva->iniciaEn()->format('H:i') }}–{{ $reserva->finalizaEn()->format('H:i') }}
                                    </div>
                                </td>
                                <td><span class="badge {{ $reserva->estadoBadge() }}">{{ $reserva->estadoOperacionLabel() }}</span></td>
                                <td>{{ $reserva->check_in_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td>{{ $reserva->check_out_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="text-end text-nowrap">Bs. {{ number_format((float) $reserva->monto_total, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No se encontraron reservas con esos filtros.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($reservas->hasPages())
            <div class="card-body border-top">
                {{ $reservas->links() }}
            </div>
        @endif
    </div>
@endsection
