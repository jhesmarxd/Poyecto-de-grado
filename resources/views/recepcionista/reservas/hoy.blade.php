@extends('layouts.app')

@section('title', 'Agenda operativa')

@section('content')
@include('recepcionista._navigation')

    @php($nombreCorto = \Illuminate\Support\Str::before(Auth::user()->name, ' '))

    <div class="hero">
        <div class="hero-text">
            <span class="eyebrow">Recepción · Operación diaria</span>
            <h1 class="hero-title">¡Hola, <span>{{ $nombreCorto }}</span>!</h1>
            <p class="hero-sub">{{ \Illuminate\Support\Carbon::parse($fecha)->translatedFormat('l d \d\e F \d\e Y') }} · Agenda operativa: registra entradas, salidas y cobros presenciales.</p>
        </div>
        <div class="hero-actions">
            <div class="hero-mascot" aria-hidden="true">
                <img src="{{ asset('img/generated/wally-team-chibi-v1.png') }}" alt="">
                <div class="hero-mascot-copy">
                    <strong>Agenda en juego</strong>
                    <span>Todo listo para recibir al próximo equipo.</span>
                </div>
            </div>
            <a href="{{ route('recepcionista.reservas.create') }}" class="btn btn--primary">Registrar reserva</a>
        </div>
    </div>

    <div class="kpi-grid mb-4">
        <div class="kpi-card c-primary">
            <div class="kpi-label">Reservas de hoy</div>
            <div class="kpi-value">{{ $resumen['total'] }}</div>
            <div class="kpi-compare"><span>agenda completa</span></div>
        </div>
        <div class="kpi-card c-success">
            <div class="kpi-label">Esperando llegada</div>
            <div class="kpi-value">{{ $resumen['por_ingresar'] }}</div>
            <div class="kpi-compare"><span>reservas confirmadas</span></div>
        </div>
        <div class="kpi-card c-primary">
            <div class="kpi-label">En cancha</div>
            <div class="kpi-value">{{ $resumen['en_curso'] }}</div>
            <div class="kpi-compare"><span>con entrada registrada</span></div>
        </div>
        <div class="kpi-card c-success">
            <div class="kpi-label">Finalizadas</div>
            <div class="kpi-value">{{ $resumen['finalizadas'] }}</div>
            <div class="kpi-compare"><span>con salida registrada</span></div>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <div>
                <h2 class="card-title">Atención de reservas</h2>
                <p class="text-muted mb-0">Cada fila muestra únicamente la siguiente acción válida.</p>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-scroll">
                <table class="table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Hora</th>
                            <th>Cliente</th>
                            <th>Cancha</th>
                            <th>Balón</th>
                            <th>Atención</th>
                            <th>Entrada / salida</th>
                            <th class="text-end">Siguiente acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reservas as $reserva)
                            <tr class="{{ $reserva->estaEnCurso() ? 'is-playing-row' : '' }}">
                                <td class="text-nowrap">
                                    <strong>{{ $reserva->iniciaEn()->format('H:i') }}</strong>
                                    <span class="text-muted">– {{ $reserva->finalizaEn()->format('H:i') }}</span>
                                </td>
                                <td>
                                    <strong>{{ $reserva->usuario->name }}</strong>
                                    @if ($reserva->usuario->telefono)
                                        <div class="text-muted small">{{ $reserva->usuario->telefono }}</div>
                                    @endif
                                </td>
                                <td>{{ $reserva->cancha->nombre }}</td>
                                <td>{{ $reserva->balonLabelCorta() }}</td>
                                <td>
                                    <span class="badge {{ $reserva->estadoBadge() }}">{{ $reserva->estadoOperacionLabel() }}</span>
                                </td>
                                <td class="small text-nowrap">
                                    <div>Entrada: {{ $reserva->check_in_at?->format('H:i') ?? '—' }}</div>
                                    <div>Salida: {{ $reserva->check_out_at?->format('H:i') ?? '—' }}</div>
                                </td>
                                <td class="text-end text-nowrap">
                                    @if ($reserva->estado === \App\Models\Reserva::ESTADO_CONFIRMADA && $reserva->check_in_at === null)
                                        <form method="POST" action="{{ route('recepcionista.checkin.store', $reserva) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn--sm btn--success">Registrar entrada</button>
                                        </form>
                                    @elseif ($reserva->estaEnCurso())
                                        <form method="POST" action="{{ route('recepcionista.checkout.store', $reserva) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn--sm btn--outline-primary">Registrar salida</button>
                                        </form>
                                    @elseif ($reserva->check_out_at !== null)
                                        <span class="text-success small">Atención finalizada</span>
                                    @else
                                        <span class="text-muted small">Sin acciones</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="sports-empty text-center text-muted py-4">
                                    <strong>La cancha está libre por ahora</strong>
                                    <span>Las nuevas reservas aparecerán aquí.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
