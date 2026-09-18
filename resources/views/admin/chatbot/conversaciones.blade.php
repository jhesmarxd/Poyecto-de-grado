@extends('layouts.app')

@section('title', 'Conversaciones del chatbot')

@section('content')
@include('admin._analysis_navigation')

    <div class="hero">
        <div class="hero-text">
            <span class="eyebrow">Inteligencia y reportes</span>
            <h1 class="hero-title">Conversaciones del <span class="accent">chatbot</span></h1>
            <p class="hero-sub">Historial de interacciones de los usuarios con el asistente.</p>
        </div>
        <div class="hero-actions">
            <a href="{{ route('admin.chatbot.conversaciones', ['solo_usuarios' => 1]) }}" class="btn btn--ghost">Solo usuarios</a>
            <a href="{{ route('admin.chatbot.conversaciones') }}" class="btn btn--primary">Todas</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @if ($conversaciones->isEmpty())
                <div class="text-center py-4">
                            <p class="text-muted mb-0">Aún no hay conversaciones registradas. Escribe al chat flotante para probarlo.</p>
                        </div>
                    @else
                        <table id="tabla-conversaciones" class="table table-striped table-hover align-middle" data-sequential-rows>
                            <thead>
                                <tr>
                                    <th>N.º</th>
                                    <th>Usuario</th>
                                    <th>Mensajes</th>
                                    <th>Contexto</th>
                                    <th>Última actividad</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($conversaciones as $conversacion)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $conversacion->usuario?->name ?? 'Visitante' }}</td>
                                        <td>
                                            <span class="badge bg-secondary">{{ $conversacion->mensajes_count }}</span>
                                        </td>
                                        <td class="small text-muted">
                                            {{ $conversacion->contexto ? \Illuminate\Support\Str::limit(json_encode($conversacion->contexto, JSON_UNESCAPED_UNICODE), 60) : '—' }}
                                        </td>
                                        <td>{{ $conversacion->updated_at->diffForHumans() }}</td>
                                        <td>
                                            <a href="{{ route('admin.chatbot.conversaciones.show', $conversacion) }}" class="btn btn-sm btn-outline-primary">Ver</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.$) {
            $('#tabla-conversaciones').DataTable({
                order: [],
                columnDefs: [{ targets: 0, orderable: false, searchable: false }],
                language: {
                    "search": "Buscar:",
                    "lengthMenu": "Mostrar _MENU_ registros",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                    "infoEmpty": "No hay registros",
                    "infoFiltered": "(filtrado de _MAX_ registros totales)",
                    "emptyTable": "No hay conversaciones",
                    "zeroRecords": "No se encontraron resultados",
                    "paginate": {
                        "previous": "Anterior",
                        "next": "Siguiente"
                    }
                }
            });
        }
    });
</script>
@endpush
