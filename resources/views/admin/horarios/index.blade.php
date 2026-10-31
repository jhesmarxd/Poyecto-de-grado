@extends('layouts.app')

@section('title', 'Franjas reservables')

@section('content')


    <div class="hero">
        <div class="hero-text">
            <span class="eyebrow">Franjas · Gestión</span>
            <h1 class="hero-title">Franjas <span class="accent">reservables</span></h1>
            <p class="hero-sub">Define las franjas horarias disponibles por cancha y día.</p>
        </div>
        @if ($cancha)
            <div class="hero-actions">
                <a href="{{ route('admin.horarios.create', $cancha) }}" class="btn btn--primary">+ Nueva franja</a>
            </div>
        @endif
    </div>

    @if ($cancha)
        <div class="card mb-4">
            <div class="card-head">
                <div class="card-title-wrap">
                    <span class="eyebrow">Configuración masiva</span>
                    <h3 class="card-title">Semana y periodo operativo</h3>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.horarios.bulk', $cancha) }}"
                      data-confirm-submit data-confirm-action="create"
                      data-confirm-title="¿Aplicar configuración de horarios?"
                      data-confirm-text="Se crearán franjas consecutivas para los días y el periodo seleccionados.">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="field-label d-block">Días habilitados</label>
                            <div class="d-flex flex-wrap gap-3">
                                @foreach (\App\Models\Horario::DIAS as $valor => $nombre)
                                    <label class="form-check">
                                        <input class="form-check-input" type="checkbox" name="dias[]" value="{{ $valor }}"
                                               @checked(in_array($valor, old('dias', [1, 2, 3, 4, 5, 6])))>
                                        <span class="form-check-label">{{ $nombre }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label for="vigente_desde" class="field-label">Desde</label>
                            <input id="vigente_desde" name="vigente_desde" type="date" class="form-control"
                                   value="{{ old('vigente_desde', now()->toDateString()) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label for="vigente_hasta" class="field-label">Hasta</label>
                            <input id="vigente_hasta" name="vigente_hasta" type="date" class="form-control"
                                   value="{{ old('vigente_hasta', now()->addMonth()->toDateString()) }}" required>
                        </div>
                        <div class="col-md-2">
                            <label for="hora_inicio_masiva" class="field-label">Apertura</label>
                            <input id="hora_inicio_masiva" name="hora_inicio" type="time" class="form-control" value="09:00" required>
                        </div>
                        <div class="col-md-2">
                            <label for="hora_fin_masiva" class="field-label">Cierre</label>
                            <input id="hora_fin_masiva" name="hora_fin" type="time" class="form-control" value="22:00" required>
                        </div>
                        <div class="col-md-2">
                            <label for="duracion" class="field-label">Cada</label>
                            <select id="duracion" name="duracion" class="form-select">
                                <option value="60">60 minutos</option>
                                <option value="30">30 minutos</option>
                                <option value="90">90 minutos</option>
                                <option value="120">120 minutos</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-actions mt-3">
                        <span class="spacer"></span>
                        <button type="submit" class="btn btn--primary">Aplicar configuración</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-head">
                <div class="card-title-wrap">
                    <span class="eyebrow">Excepciones</span>
                    <h3 class="card-title">Cierres por fecha</h3>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.horarios.closures.store', $cancha) }}"
                      class="row g-3 align-items-end" data-confirm-submit data-confirm-action="create">
                    @csrf
                    <div class="col-md-4">
                        <label for="fecha_cierre" class="field-label">Fecha cerrada</label>
                        <input id="fecha_cierre" name="fecha_cierre" type="date" class="form-control"
                               min="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="col-md-6">
                        <label for="motivo" class="field-label">Motivo</label>
                        <input id="motivo" name="motivo" class="form-control" maxlength="255"
                               placeholder="Ej.: mantenimiento o feriado">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn--primary w-100" type="submit">Registrar cierre</button>
                    </div>
                </form>

                @if ($cierres->isNotEmpty())
                    <div class="table-responsive mt-3">
                        <table class="table align-middle mb-0">
                            <thead><tr><th>Fecha</th><th>Motivo</th><th></th></tr></thead>
                            <tbody>
                                @foreach ($cierres as $cierre)
                                    <tr>
                                        <td>{{ $cierre->fecha->translatedFormat('d/m/Y') }}</td>
                                        <td>{{ $cierre->motivo ?: 'Sin motivo indicado' }}</td>
                                        <td class="text-end">
                                            <form method="POST" action="{{ route('admin.horarios.closures.destroy', [$cancha, $cierre]) }}"
                                                  data-confirm-submit data-confirm-action="delete">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger" type="submit">Quitar cierre</button>
                                            </form>
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

    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.horarios.index') }}" class="row g-2 align-items-center mb-3" id="form-cancha">
                        <div class="col-auto">
                            <label for="cancha_id" class="col-form-label">Cancha:</label>
                        </div>
                        <div class="col-auto">
                            <select id="cancha_id" name="cancha_id" class="form-select" onchange="document.getElementById('form-cancha').submit()">
                                @foreach ($canchas as $c)
                                    <option value="{{ $c->id }}" @selected($cancha && $cancha->id === $c->id)>{{ $c->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                    </form>

                    @if ($cancha)
                        <table id="tabla-horarios" class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Día</th>
                                    <th>Hora inicio</th>
                                    <th>Hora fin</th>
                                    <th>Vigencia</th>
                                    <th>Disponible</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($horarios as $horario)
                                    <tr>
                                        <td>{{ $horario->nombreDia() }}</td>
                                        <td>{{ \Carbon\Carbon::parse($horario->hora_inicio)->format('H:i') }}</td>
                                        <td>{{ \Carbon\Carbon::parse($horario->hora_fin)->format('H:i') }}</td>
                                        <td class="small">
                                            {{ $horario->vigente_desde?->format('d/m/Y') ?? 'Sin inicio' }}
                                            – {{ $horario->vigente_hasta?->format('d/m/Y') ?? 'Sin fin' }}
                                        </td>
                                        <td>
                                            @if ($horario->disponible)
                                                <span class="badge bg-success">Sí</span>
                                            @else
                                                <span class="badge bg-danger">No</span>
                                            @endif
                                        </td>
                                        <td class="text-nowrap">
                                            <a href="{{ route('admin.horarios.edit', [$cancha, $horario]) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmarEliminacion(event, {{ $horario->id }})">Eliminar</button>
                                            <form id="form-eliminar-{{ $horario->id }}" action="{{ route('admin.horarios.destroy', [$cancha, $horario]) }}" method="POST" class="d-none">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        @if ($horarios->isEmpty())
                            <p class="text-muted mb-0">Esta cancha no tiene franjas reservables configuradas aún.</p>
                        @endif
                    @else
                        <p class="text-muted mb-0">Primero debes registrar al menos una cancha.</p>
                    @endif
                </div>
            </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.$) {
            $('#tabla-horarios').DataTable({
                language: {
                    "search": "Buscar:",
                    "lengthMenu": "Mostrar _MENU_ registros",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                    "infoEmpty": "No hay registros",
                    "infoFiltered": "(filtrado de _MAX_ registros totales)",
                    "emptyTable": "No hay horarios configurados",
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
            title: '¿Eliminar horario?',
            text: 'Las reservas históricas conservarán su periodo registrado.'
        });
    }
</script>
@endpush
