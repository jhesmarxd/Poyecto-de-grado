@extends('layouts.app')

@section('title', 'Revisar cambio operativo')

@section('content')
<div class="container py-4">
    @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    <div class="alert alert-warning">
        <h1 class="h4">Atención: este cambio afecta reservas existentes</h1>
        <p class="mb-0">Cambio #{{ $cambio->id }} · {{ $cambio->motivo }}. Revisa los clientes antes de aplicarlo.</p>
    </div>
    <div class="card shadow-sm mb-4">
        <div class="table-responsive"><table class="table mb-0 align-middle"><thead><tr><th>Cliente</th><th>Fecha</th><th>Horario</th><th>Cancha</th><th>Estado</th><th>Notificación</th><th>Resolución</th></tr></thead><tbody>
        @forelse($cambio->reservasAfectadas as $afectada)
            @php($reserva = $afectada->reserva)
            <tr>
                <td>{{ $reserva->usuario?->name }}</td><td>{{ $reserva->fecha->format('d/m/Y') }}</td><td>{{ substr($reserva->hora_inicio_reserva,0,5) }}–{{ substr($reserva->hora_fin_reserva,0,5) }}</td><td>{{ $reserva->cancha?->nombre }}</td><td>{{ $reserva->estadoLabel() }}</td><td>{{ $afectada->estado_notificacion }}</td>
                <td style="min-width: 320px">
                    @if($reserva->estado === \App\Models\Reserva::ESTADO_AFECTADA)
                        <form method="POST" action="{{ route('admin.configuracion-reservas.affected.cancel', $afectada) }}" class="mb-2">@csrf<button class="btn btn-sm btn-outline-danger">Cancelar por el complejo</button></form>
                        <details><summary class="small text-primary">Reprogramar</summary>
                            <form method="POST" action="{{ route('admin.configuracion-reservas.affected.reschedule', $afectada) }}" class="row g-1 mt-1">@csrf
                                <div class="col-5"><input class="form-control form-control-sm" type="date" name="fecha" min="{{ today()->toDateString() }}" required></div>
                                <div class="col-7"><select class="form-select form-select-sm" name="horario_id" required><option value="">Cancha y franja</option>@foreach($canchas as $cancha)@foreach($cancha->horarios as $horario)<option value="{{ $horario->id }}" data-cancha="{{ $cancha->id }}">{{ $cancha->nombre }} · {{ $horario->nombreDia() }} {{ substr($horario->hora_inicio,0,5) }}–{{ substr($horario->hora_fin,0,5) }}</option>@endforeach @endforeach</select></div>
                                <div class="col-5"><select class="form-select form-select-sm" name="cancha_id" required>@foreach($canchas as $cancha)<option value="{{ $cancha->id }}">{{ $cancha->nombre }}</option>@endforeach</select></div>
                                <div class="col-4"><input class="form-control form-control-sm" type="time" name="hora_fin" required></div>
                                <div class="col-3"><button class="btn btn-sm btn-primary w-100">Guardar</button></div>
                            </form>
                        </details>
                    @elseif($afectada->resuelto_at)
                        <span class="text-success small">Resuelta {{ $afectada->resuelto_at->format('d/m/Y H:i') }}</span>
                    @else
                        <span class="text-muted small">Disponible después de aplicar</span>
                    @endif
                </td>
            </tr>
        @empty<tr><td colspan="7">No se encontraron reservas afectadas.</td></tr>@endforelse
        </tbody></table></div>
    </div>
    @if($cambio->estado === \App\Models\CambioConfiguracionReserva::ESTADO_PENDIENTE)
        <div class="d-flex gap-2">
            <form method="POST" action="{{ route('admin.configuracion-reservas.cancel', $cambio) }}">@csrf<button class="btn btn-outline-secondary">Volver y descartar</button></form>
            <form method="POST" action="{{ route('admin.configuracion-reservas.apply', $cambio) }}">@csrf<button class="btn btn-danger">Notificar y aplicar cambio</button></form>
        </div>
    @else
        <a class="btn btn-outline-primary" href="{{ route('admin.configuracion-reservas.index') }}">Volver a configuración</a>
    @endif
</div>
@endsection
