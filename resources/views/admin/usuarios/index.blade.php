@extends('layouts.app')

@section('title', 'Usuarios y accesos')

@section('content')
    <div class="hero">
        <div class="hero-text">
            <span class="eyebrow">Administración · Accesos</span>
            <h1 class="hero-title">Usuarios y <span class="accent">accesos</span></h1>
            <p class="hero-sub">Crea, edita y administra las cuentas del sistema.</p>
        </div>
        <div class="hero-actions">
            <a href="{{ route('admin.usuarios.create') }}" class="btn btn--primary">+ Nuevo usuario</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-scroll">
                <table id="tabla-usuarios" class="table table-striped table-hover align-middle" data-sequential-rows>
                        <thead>
                            <tr>
                                <th>N.º</th>
                                <th>Nombre</th>
                                <th>Correo</th>
                                <th>Teléfono</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Registrado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($usuarios as $usuario)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $usuario->name }}</td>
                                    <td>{{ $usuario->email }}</td>
                                    <td>{{ $usuario->telefono ?? '—' }}</td>
                                    <td>
                                        @if ($usuario->esAdministrador())
                                            <span class="badge bg-primary">Admin</span>
                                        @else
                                            <span class="badge bg-secondary">Cliente</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($usuario->estaActivo())
                                            <span class="badge bg-success">Activo</span>
                                        @else
                                            <span class="badge bg-danger">Inactivo</span>
                                        @endif
                                    </td>
                                    <td>{{ $usuario->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="text-nowrap">
                                        <a href="{{ route('admin.usuarios.edit', $usuario) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                                        @if ($usuario->id !== Auth::id())
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmarEliminacion(event, {{ $usuario->id }})">Eliminar</button>
                                            <form id="form-eliminar-{{ $usuario->id }}" action="{{ route('admin.usuarios.destroy', $usuario) }}" method="POST" class="d-none">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        @endif
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
            $('#tabla-usuarios').DataTable({
                order: [],
                columnDefs: [{ targets: 0, orderable: false, searchable: false }],
                language: {
                    "search": "Buscar:",
                    "lengthMenu": "Mostrar _MENU_ registros",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                    "infoEmpty": "No hay registros",
                    "infoFiltered": "(filtrado de _MAX_ registros totales)",
                    "emptyTable": "No hay usuarios registrados",
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
            title: '¿Eliminar usuario?',
            text: 'Esta acción no se puede deshacer.'
        });
    }
</script>
@endpush
