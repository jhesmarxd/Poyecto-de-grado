@extends('layouts.app')

@section('title', 'Gestión de Canchas')

@section('content')
@include('admin._resources_navigation')

    <div class="hero">
        <div class="hero-text">
            <span class="eyebrow">Canchas · Gestión</span>
            <h1 class="hero-title">Gestión de <span class="accent">canchas</span></h1>
            <p class="hero-sub">Administra las canchas, sus precios y franjas disponibles.</p>
        </div>
        <div class="hero-actions">
            <a href="{{ route('admin.canchas.create') }}" class="btn btn--primary">+ Nueva cancha</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-scroll">
                <table id="tabla-canchas" class="table table-striped table-hover align-middle" data-sequential-rows>
                        <thead>
                            <tr>
                                <th>N.º</th>
                                <th>Imagen</th>
                                <th>Nombre</th>
                                <th>Recinto</th>
                                <th>Ubicación</th>
                                <th>Precio/hora</th>
                                <th>Franjas</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($canchas as $cancha)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        @if ($cancha->imagenUrl())
                                            <img src="{{ $cancha->imagenUrl() }}" alt="{{ $cancha->nombre }}" class="rounded" style="width: 56px; height: 40px; object-fit: cover;">
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>{{ $cancha->nombre }}</td>
                                    <td>
                                        @if ($cancha->esCerrada())
                                            <span class="badge bg-info text-dark">Cerrada</span>
                                        @else
                                            <span class="badge bg-secondary">Abierta</span>
                                        @endif
                                    </td>
                                    <td>{{ $cancha->ubicacion ?? '—' }}</td>
                                    <td>Bs. {{ number_format($cancha->precio_por_hora, 2) }}</td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $cancha->horarios_count }}</span>
                                    </td>
                                    <td>
                                        @if ($cancha->estaDisponible())
                                            <span class="badge bg-success">{{ \App\Models\Cancha::ESTADOS[$cancha->estado] }}</span>
                                        @elseif ($cancha->estado === \App\Models\Cancha::ESTADO_MANTENIMIENTO)
                                            <span class="badge bg-warning text-dark">{{ \App\Models\Cancha::ESTADOS[$cancha->estado] }}</span>
                                        @else
                                            <span class="badge bg-danger">{{ \App\Models\Cancha::ESTADOS[$cancha->estado] }}</span>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">
                                        <a href="{{ route('admin.horarios.index', ['cancha_id' => $cancha->id]) }}" class="btn btn-sm btn-outline-secondary">Franjas</a>
                                        <a href="{{ route('admin.canchas.edit', $cancha) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmarEliminacion(event, {{ $cancha->id }})">Eliminar</button>
                                        <form id="form-eliminar-{{ $cancha->id }}" action="{{ route('admin.canchas.destroy', $cancha) }}" method="POST" class="d-none">
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
            $('#tabla-canchas').DataTable({
                order: [],
                columnDefs: [{ targets: 0, orderable: false, searchable: false }],
                language: {
                    "search": "Buscar:",
                    "lengthMenu": "Mostrar _MENU_ registros",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                    "infoEmpty": "No hay registros",
                    "infoFiltered": "(filtrado de _MAX_ registros totales)",
                    "emptyTable": "No hay canchas registradas",
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
            title: '¿Eliminar cancha?',
            text: 'Esta acción eliminará también sus horarios.'
        });
    }
</script>
@endpush
