@extends('layouts.app')

@section('title', 'Gestión de Reservas')

@section('content')
    <div class="hero">
        <div class="hero-text">
            <span class="eyebrow">Reservas · Gestión</span>
            <h1 class="hero-title">Gestión de <span class="accent">reservas</span></h1>
            <p class="hero-sub">Filtra, cambia estados y administra todas las reservas del sistema.</p>
        </div>
        <div class="hero-actions">
            <a href="{{ route('admin.reservas.calendario') }}" class="btn btn--primary">Ver calendario</a>
        </div>
    </div>

    @include('admin.reservas._navigation')

    <div class="card">
        <div class="card-body p-0">
            <form method="GET" action="{{ route('admin.reservas.index') }}" class="row g-2 align-items-end px-4 pt-4 pb-2">
                <div class="col-md-4">
                    <label for="estado" class="form-label small">Estado</label>
                    <select name="estado" id="estado" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        @foreach (\App\Models\Reserva::ESTADOS as $clave => $etiqueta)
                            <option value="{{ $clave }}" @selected($estado === $clave)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="cancha_id" class="form-label small">Cancha</label>
                    <select name="cancha_id" id="cancha_id" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        @foreach ($canchas as $cancha)
                            <option value="{{ $cancha->id }}" @selected((int) $canchaId === $cancha->id)>{{ $cancha->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 pb-1">
                    <button type="submit" class="btn btn--ghost btn-sm">Filtrar</button>
                    <a href="{{ route('admin.reservas.index') }}" class="btn btn--ghost btn-sm">Limpiar</a>
                </div>
            </form>

            <div class="table-scroll px-4 pb-4">
                <table id="tabla-reservas" class="table table-striped table-hover align-middle" data-sequential-rows>
                    <thead>
                        <tr>
                            <th>N.º</th>
                            <th>Cliente</th>
                            <th>Teléfono</th>
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
                                <td>{{ $reserva->usuario->name }}</td>
                                <td>{{ $reserva->usuario->telefono ?? '—' }}</td>
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
                                <td>
                                    <span class="badge {{ $reserva->estadoBadge() }}">{{ $reserva->estadoLabel() }}</span>
                                </td>
                                <td class="text-nowrap">
                                    @if (! in_array($reserva->estado, [\App\Models\Reserva::ESTADO_CANCELADA, \App\Models\Reserva::ESTADO_COMPLETADA], true))
                                        <form action="{{ route('admin.reservas.estado', $reserva) }}" method="POST" class="d-inline">
                                            @csrf
                                            <select name="estado" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.requestSubmit()">
                                                @foreach (\App\Models\Reserva::ESTADOS_GESTIONABLES as $clave => $etiqueta)
                                                    <option value="{{ $clave }}" @selected($reserva->estado === $clave)>{{ $etiqueta }}</option>
                                                @endforeach
                                            </select>
                                        </form>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif

                                    <button type="button" class="btn btn-sm btn-outline-danger ms-1"
                                            onclick="confirmarEliminacion(event, {{ $reserva->id }})">Eliminar</button>
                                    <form id="form-eliminar-{{ $reserva->id }}" action="{{ route('admin.reservas.destroy', $reserva) }}" method="POST" class="d-none">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.$) {
            $('#tabla-reservas').DataTable({
                order: [],
                columnDefs: [{ targets: 0, orderable: false, searchable: false }],
                language: {
                    "search": "Buscar:",
                    "lengthMenu": "Mostrar _MENU_ registros",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                    "infoEmpty": "No hay registros",
                    "infoFiltered": "(filtrado de _MAX_ registros totales)",
                    "emptyTable": "No hay reservas registradas",
                    "zeroRecords": "No se encontraron resultados",
                    "paginate": {
                        "previous": "Anterior",
                        "next": "Siguiente"
                    }
                }
            });
        }
    });

    function confirmarEliminacion(event, id) {
        event.preventDefault();
        window.WallyCrud.confirmForm('form-eliminar-' + id, {
            action: 'delete',
            title: '¿Eliminar reserva?',
            text: 'Esta acción no se puede deshacer.'
        });
    }
</script>
@endpush
