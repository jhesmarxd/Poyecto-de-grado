@extends('layouts.app')

@section('title', 'Mis reservas')

@section('content')
    <div class="hero container-wide">
        <div class="hero-text">
            <span class="eyebrow">Mi actividad deportiva</span>
            <h1 class="hero-title">Mis <span class="accent">reservas</span></h1>
            <p class="hero-sub">Consulta tus próximas reservas en calendario o revisa su historial en el listado.</p>
        </div>
        <div class="hero-actions">
            <a href="{{ route('disponibilidad.index') }}" class="btn btn--primary">Consultar y reservar</a>
        </div>
    </div>

    <div class="container-wide">
        @if ($proximaReserva)
            @include('reservas.partials.proxima-reserva', [
                'reserva' => $proximaReserva,
                'ahoraServidor' => $ahoraServidor,
            ])
        @endif

        <nav class="tabs pills" aria-label="Vistas de mis reservas">
            <a class="tab {{ $vista === 'calendario' ? 'is-active' : '' }}"
               href="{{ route('reservas.mis') }}">Calendario</a>
            <a class="tab {{ $vista === 'listado' ? 'is-active' : '' }}"
               href="{{ route('reservas.mis', ['vista' => 'listado']) }}">Listado</a>
        </nav>

        @if ($vista === 'calendario')
            @include('reservas.partials.calendar', [
                'calendarRole' => 'cliente',
                'eventsUrl' => route('reservas.mis.eventos'),
            ])
        @else
            <div class="card">
                <div class="card-body">
                    @if ($reservas->isEmpty())
                        <div class="text-center py-4">
                            <p class="text-muted mb-3">Todavía no tienes reservas.</p>
                            <a href="{{ route('disponibilidad.index') }}" class="btn btn--primary">Consultar disponibilidad</a>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>N.º</th>
                                        <th>Cancha</th>
                                        <th>Fecha</th>
                                        <th>Hora</th>
                                        <th>Balón</th>
                                        <th>Monto</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($reservas as $reserva)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $reserva->cancha->nombre }}</td>
                                            <td>{{ $reserva->fecha->translatedFormat('d/m/Y') }}</td>
                                            <td>
                                                {{ $reserva->iniciaEn()->format('H:i') }}
                                                – {{ $reserva->finalizaEn()->format('H:i') }}
                                            </td>
                                            <td>
                                                @php($claseBalon = match ($reserva->tipo_balon) {
                                                    \App\Models\Reserva::BALON_PREMIUM => 'text-bg-warning',
                                                    \App\Models\Reserva::BALON_PROPIO => 'bg-light text-dark border',
                                                    default => 'bg-success',
                                                })
                                                <span class="badge {{ $claseBalon }}">{{ $reserva->balonLabelCorta() }}</span>
                                            </td>
                                            <td>Bs. {{ number_format($reserva->monto_total, 2) }}</td>
                                            <td><span class="badge {{ $reserva->estadoBadge() }}">{{ $reserva->estadoLabel() }}</span></td>
                                            <td class="text-nowrap">
                                                @if ($reserva->esCancelablePorCliente())
                                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                                            onclick="confirmarCancelacion(event, {{ $reserva->id }})">Cancelar</button>
                                                    <form id="form-cancelar-{{ $reserva->id }}"
                                                          action="{{ route('reservas.cancelar', $reserva) }}"
                                                          method="POST" class="d-none">
                                                        @csrf
                                                    </form>
                                                @else
                                                    <span class="text-muted small">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
@endsection

@push('scripts')
<script>
    function confirmarCancelacion(event, id) {
        event.preventDefault();
        window.WallyCrud.confirmForm('form-cancelar-' + id, {
            action: 'delete',
            title: '¿Cancelar reserva?',
            text: 'El horario y el balón quedarán libres para otros clientes.',
            confirmText: 'Sí, cancelar'
        });
    }
</script>
@endpush
