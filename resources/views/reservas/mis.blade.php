@extends('layouts.app')

@section('title', 'Mis reservas')

@section('content')
    <div class="client-page client-reservations-page">
        <header class="client-page-header client-page-header--with-action">
            <div class="client-page-header__icon" aria-hidden="true"><i class="bi bi-calendar2-check"></i></div>
            <div>
                <span class="client-kicker">Tu actividad deportiva</span>
                <h1>Mis <span>reservas</span></h1>
                <p>Consulta tus próximos turnos en el calendario o revisa todo tu historial.</p>
            </div>
            <a href="{{ route('disponibilidad.index') }}" class="btn btn--primary client-page-header__action">
                <i class="bi bi-plus-circle" aria-hidden="true"></i>
                <span><strong>Nueva reserva</strong><small>Consultar disponibilidad</small></span>
                <i class="bi bi-chevron-right" aria-hidden="true"></i>
            </a>
        </header>

        @if ($proximaReserva)
            @include('reservas.partials.proxima-reserva', [
                'reserva' => $proximaReserva,
                'ahoraServidor' => $ahoraServidor,
            ])
        @endif

        <nav class="tabs pills client-view-tabs" aria-label="Vistas de mis reservas">
            <a class="tab {{ $vista === 'calendario' ? 'is-active' : '' }}"
               href="{{ route('reservas.mis') }}" @if($vista === 'calendario') aria-current="page" @endif>
                <i class="bi bi-calendar3" aria-hidden="true"></i> Calendario
            </a>
            <a class="tab {{ $vista === 'listado' ? 'is-active' : '' }}"
               href="{{ route('reservas.mis', ['vista' => 'listado']) }}" @if($vista === 'listado') aria-current="page" @endif>
                <i class="bi bi-list-ul" aria-hidden="true"></i> Listado
            </a>
        </nav>

        @if ($vista === 'calendario')
            @include('reservas.partials.calendar', [
                'calendarRole' => 'cliente',
                'eventsUrl' => route('reservas.mis.eventos'),
            ])
        @else
            <div class="card client-reservation-list">
                <div class="card-body">
                    @if ($reservas->isEmpty())
                        <div class="client-empty-state">
                            <i class="bi bi-calendar2-plus" aria-hidden="true"></i>
                            <strong>Todavía no tienes reservas</strong>
                            <span>Cuando reserves una cancha, podrás verla y gestionarla aquí.</span>
                            <a href="{{ route('disponibilidad.index') }}" class="btn btn--primary">Reservar una cancha</a>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle client-reservations-table">
                                <thead>
                                    <tr>
                                        <th>N.º</th>
                                        <th>Cancha</th>
                                        <th>Fecha</th>
                                        <th>Hora</th>
                                        <th>Balón</th>
                                        <th>Monto</th>
                                        <th>Estado</th>
                                        <th>Google Calendar</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($reservas as $reserva)
                                        <tr>
                                            <td data-label="Reserva">#{{ $loop->iteration }}</td>
                                            <td data-label="Cancha"><strong>{{ $reserva->cancha->nombre }}</strong></td>
                                            <td data-label="Fecha">{{ $reserva->fecha->translatedFormat('d/m/Y') }}</td>
                                            <td data-label="Horario">
                                                {{ $reserva->iniciaEn()->format('H:i') }}
                                                – {{ $reserva->finalizaEn()->format('H:i') }}
                                            </td>
                                            <td data-label="Balón">
                                                @php($claseBalon = match ($reserva->tipo_balon) {
                                                    \App\Models\Reserva::BALON_PREMIUM => 'text-bg-warning',
                                                    \App\Models\Reserva::BALON_PROPIO => 'bg-light text-dark border',
                                                    default => 'bg-success',
                                                })
                                                <span class="badge {{ $claseBalon }}">{{ $reserva->balonLabelCorta() }}</span>
                                            </td>
                                            <td data-label="Monto">Bs. {{ number_format($reserva->monto_total, 2) }}</td>
                                            <td data-label="Estado">
                                                <span class="badge {{ $reserva->estadoBadge() }}"><i class="bi bi-circle-fill" aria-hidden="true"></i> {{ $reserva->estadoLabel() }}</span>
                                                @if ($reserva->afectacionConfiguracion && in_array($reserva->estado, [\App\Models\Reserva::ESTADO_AFECTADA, \App\Models\Reserva::ESTADO_CANCELADA_COMPLEJO, \App\Models\Reserva::ESTADO_REPROGRAMADA], true))
                                                    <small class="d-block text-danger mt-1">{{ $reserva->afectacionConfiguracion->cambio?->motivo ?? 'Cambio operativo del complejo' }}</small>
                                                @endif
                                            </td>
                                            <td data-label="Google Calendar">
                                                @php($google = $reserva->googleCalendarEvent)
                                                @if ($google?->sync_status === \App\Models\GoogleCalendarEvent::STATUS_SYNCED && $google->urlSegura())
                                                    <a href="{{ $google->urlSegura() }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary">Ver en Google</a>
                                                @elseif ($google?->sync_status === \App\Models\GoogleCalendarEvent::STATUS_PENDING)
                                                    <span class="text-muted small">Sincronizando</span>
                                                @elseif ($google?->sync_status === \App\Models\GoogleCalendarEvent::STATUS_ERROR)
                                                    <span class="text-muted small">No disponible</span>
                                                @elseif (in_array($google?->sync_status, [\App\Models\GoogleCalendarEvent::STATUS_CANCELLED, \App\Models\GoogleCalendarEvent::STATUS_DELETED], true))
                                                    <span class="text-muted small">Cita cancelada</span>
                                                @else
                                                    <span class="text-muted small">Aún no agendada</span>
                                                @endif
                                            </td>
                                            <td data-label="Acciones" class="text-nowrap">
                                                @if ($reserva->esCancelablePorCliente())
                                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                                            onclick="confirmarCancelacion(event, {{ $reserva->id }})">Cancelar</button>
                                                    <form id="form-cancelar-{{ $reserva->id }}"
                                                          action="{{ route('reservas.cancelar', $reserva) }}"
                                                          method="POST" class="d-none">
                                                        @csrf
                                                    </form>
                                                @else
                                                    <span class="text-muted small">Sin acciones</span>
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
