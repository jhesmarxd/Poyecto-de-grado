@extends('layouts.app')

@section('title', 'Reportes operativos')

@section('content')

    <div class="hero">
        <div class="hero-text">
            <span class="eyebrow">Inteligencia y reportes</span>
            <h1 class="hero-title">Reportes <span class="accent">operativos</span></h1>
            <p class="hero-sub">Filtra por rango de fechas y exporta los datos a CSV.</p>
        </div>
        <div class="hero-actions">
            <a href="{{ route('admin.dashboard') }}" class="btn btn--ghost">Dashboard</a>
            <button type="button" class="btn btn--primary" id="btn-exportar">Exportar CSV</button>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <form method="GET" action="{{ route('admin.reportes.index') }}" class="row g-2 align-items-end px-4 pt-4 pb-2">
                        <div class="col-md-3">
                            <label for="desde" class="form-label small">Desde</label>
                            <input type="date" name="desde" id="desde" class="form-control form-control-sm" value="{{ $desde }}">
                        </div>
                        <div class="col-md-3">
                            <label for="hasta" class="form-label small">Hasta</label>
                            <input type="date" name="hasta" id="hasta" class="form-control form-control-sm" value="{{ $hasta }}">
                        </div>
                        <div class="col-md-2">
                            <label for="estado" class="form-label small">Estado</label>
                            <select name="estado" id="estado" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                @foreach (\App\Models\Reserva::ESTADOS as $clave => $etiqueta)
                                    <option value="{{ $clave }}" @selected($estado === $clave)>{{ $etiqueta }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="cancha_id" class="form-label small">Cancha</label>
                            <select name="cancha_id" id="cancha_id" class="form-select form-select-sm">
                                <option value="">Todas</option>
                                @foreach ($canchas as $cancha)
                                    <option value="{{ $cancha->id }}" @selected((int) $canchaId === $cancha->id)>{{ $cancha->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-success btn-sm w-100">Filtrar</button>
                        </div>
                    </form>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <div class="border rounded p-2 text-center">
                                <div class="fw-bold">{{ $resumen['total'] }}</div>
                                <div class="small text-muted">Reservas en el rango</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-2 text-center">
                                <div class="fw-bold text-success">Bs. {{ number_format($resumen['ingresos'], 2) }}</div>
                                <div class="small text-muted">Ingresos estimados</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-2 text-center">
                                <div class="fw-bold text-primary">{{ $resumen['activas'] }}</div>
                                <div class="small text-muted">Reservas activas</div>
                            </div>
                        </div>
                    </div>

                    <table id="tabla-reporte" class="table table-striped table-hover align-middle" data-sequential-rows>
                        <thead>
                            <tr>
                                <th>N.º</th>
                                <th>Cliente</th>
                                <th>Cancha</th>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Monto</th>
                                <th>Estado</th>
                                <th>Observaciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($reservas as $reserva)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $reserva->usuario->name }}</td>
                                    <td>{{ $reserva->cancha->nombre }}</td>
                                    <td>{{ $reserva->fecha->translatedFormat('d/m/Y') }}</td>
                                    <td>
                                        {{ $reserva->iniciaEn()->format('H:i') }}
                                        – {{ $reserva->finalizaEn()->format('H:i') }}
                                    </td>
                                    <td>Bs. {{ number_format($reserva->monto_total, 2) }}</td>
                                    <td>
                                        <span class="badge {{ $reserva->estadoBadge() }}">{{ $reserva->estadoLabel() }}</span>
                                    </td>
                                    <td class="small text-muted">{{ $reserva->observaciones ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.$) {
            $('#tabla-reporte').DataTable({
                order: [],
                columnDefs: [{ targets: 0, orderable: false, searchable: false }],
                language: {
                    "search": "Buscar:",
                    "lengthMenu": "Mostrar _MENU_ registros",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                    "infoEmpty": "No hay registros",
                    "infoFiltered": "(filtrado de _MAX_ registros totales)",
                    "emptyTable": "No hay reservas en el rango seleccionado",
                    "zeroRecords": "No se encontraron resultados",
                    "paginate": {
                        "previous": "Anterior",
                        "next": "Siguiente"
                    }
                }
            });
        }

        // Exportación CSV con los filtros actuales
        document.getElementById('btn-exportar').addEventListener('click', function () {
            const params = new URLSearchParams({
                desde: document.getElementById('desde').value,
                hasta: document.getElementById('hasta').value,
                estado: document.getElementById('estado').value,
                cancha_id: document.getElementById('cancha_id').value
            });
            window.location.href = @json(route('admin.reportes.exportar')) + '?' + params.toString();
        });
    });
</script>
@endpush
