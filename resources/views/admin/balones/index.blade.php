@extends('layouts.app')

@section('title', 'Balones')

@section('content')


    <!-- Encabezado compacto -->
    <div class="hero py-2 mb-2">
        <div class="hero-text">
            <span class="eyebrow" style="font-size: 0.8rem;">Balones · Gestión</span>
            <h1 class="hero-title" style="font-size: 1.6rem; margin-bottom: 0;">Catálogo de <span class="accent">balones</span></h1>
            <p class="hero-sub" style="font-size: 0.9rem; margin-bottom: 0;">Registra cada balón con su marca, color, calidad, precio por hora y foto.</p>
        </div>
        <div class="hero-actions">
            <a href="{{ route('admin.balones.create') }}" class="btn btn--primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i> Nuevo balón
            </a>
        </div>
    </div>

    <!-- Contenedor con filas y columnas adaptadas a Bootstrap Grid -->
    <div class="row g-3">
        @foreach ($balones as $balon)
            <!-- Cambiado de col-6 a col-md-4 para que entren exactamente 3 por fila -->
            <div class="col-md-4">
                <div class="card h-100 shadow-sm" style="padding: 1rem 1rem;">
                    <div class="card-head mb-2 d-flex justify-content-between align-items-center">
                        <div class="card-title-wrap">
                            <span class="eyebrow" style="font-size: 0.75rem;">{{ $balon->tipoLabel() }}</span>
                            <h3 class="card-title" style="font-size: 1.1rem; margin: 0;">{{ $balon->marcaModelo() }}</h3>
                        </div>
                        @if ($balon->destacado)
                            <span class="badge text-bg-info">Destacado</span>
                        @endif
                    </div>
                    
                    <div class="card-body p-0">
                        <!-- Imagen compacta -->
                        <div class="text-center mb-2">
                            @if ($balon->imagenUrl())
                                <img src="{{ $balon->imagenUrl() }}" alt="{{ $balon->marcaModelo() }}"
                                     class="img-fluid rounded" style="max-height: 140px; object-fit: contain;">
                            @else
                                <div class="border rounded d-flex align-items-center justify-content-center"
                                     style="height: 140px; background: rgba(var(--color-primario-rgb), 0.06);">
                                    <span class="text-muted" style="font-size: 0.85rem;">
                                        <i class="bi bi-image fs-3 d-block text-center mb-1"></i>
                                        Sin foto
                                    </span>
                                </div>
                            @endif
                        </div>

                        <!-- Lista de detalles más compacta -->
                        <ul class="list-unstyled mb-2" style="font-size: 0.85rem;">
                            <li class="mb-1"><strong>Marca:</strong> {{ $balon->marca ?: '—' }}</li>
                            <li class="mb-1"><strong>Color:</strong> {{ $balon->color ?: '—' }}</li>
                            <li class="mb-1"><strong>Calidad:</strong> {{ $balon->calidad ?: '—' }}</li>
                            <li class="mb-1"><strong>Precio/h:</strong> Bs. {{ number_format($balon->precio_por_hora, 2) }}</li>
                            <li class="mb-1 text-muted" style="font-size: 0.75rem;"><strong>Actualizado:</strong> {{ $balon->updated_at->translatedFormat('d/m/Y H:i') }}</li>
                        </ul>

                        @if ($balon->descripcion)
                            <p class="card-text text-muted small mb-2" style="font-size: 0.8rem;">{{ $balon->descripcion }}</p>
                        @endif

                        <!-- Botones de acción alineados -->
                        <div class="form-actions d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                            <form method="POST" action="{{ route('admin.balones.destroy', $balon) }}" class="d-inline"
                                  data-confirm-submit data-confirm-action="delete"
                                  data-confirm-title="¿Eliminar balón?"
                                  data-confirm-text="Se eliminará también su imagen asociada.">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn--danger btn-sm">
                                    <i class="bi bi-trash me-1"></i> Eliminar
                                </button>
                            </form>
                            
                            <a href="{{ route('admin.balones.edit', $balon) }}" class="btn btn--primary btn-sm">
                                <i class="bi bi-pencil me-1"></i> Editar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection