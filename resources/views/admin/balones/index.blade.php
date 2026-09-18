@extends('layouts.app')

@section('title', 'Balones')

@section('content')
@include('admin._resources_navigation')

    <div class="hero">
        <div class="hero-text">
            <span class="eyebrow">Balones · Gestión</span>
            <h1 class="hero-title">Catálogo de <span class="accent">balones</span></h1>
            <p class="hero-sub">Registra cada balón con su marca, color, calidad, precio por hora y foto. El cliente los ve en tiempo real.</p>
        </div>
        <div class="hero-actions">
            <a href="{{ route('admin.balones.create') }}" class="btn btn--primary">
                <i class="bi bi-plus-lg me-1"></i> Nuevo balón
            </a>
        </div>
    </div>

    <div class="grid">
        @foreach ($balones as $balon)
            <div class="card col-6">
                <div class="card-head">
                    <div class="card-title-wrap">
                        <span class="eyebrow">{{ $balon->tipoLabel() }}</span>
                        <h3 class="card-title">{{ $balon->marcaModelo() }}</h3>
                    </div>
                    @if ($balon->destacado)
                        <span class="badge text-bg-info">Destacado</span>
                    @endif
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        @if ($balon->imagenUrl())
                            <img src="{{ $balon->imagenUrl() }}" alt="{{ $balon->marcaModelo() }}"
                                 class="img-fluid rounded" style="max-height: 200px; object-fit: contain;">
                        @else
                            <div class="border rounded d-flex align-items-center justify-content-center"
                                 style="height: 200px; background: rgba(var(--color-primario-rgb), 0.06);">
                                <span class="text-muted">
                                    <i class="bi bi-image fs-1 d-block text-center mb-2"></i>
                                    Sin foto · Sube la imagen del balón
                                </span>
                            </div>
                        @endif
                    </div>

                    <ul class="list-unstyled mb-3">
                        <li class="mb-2"><strong>Marca:</strong> {{ $balon->marca ?: '—' }}</li>
                        <li class="mb-2"><strong>Color:</strong> {{ $balon->color ?: '—' }}</li>
                        <li class="mb-2"><strong>Calidad:</strong> {{ $balon->calidad ?: '—' }}</li>
                        <li class="mb-2"><strong>Precio por hora:</strong> Bs. {{ number_format($balon->precio_por_hora, 2) }}</li>
                        <li class="mb-2"><strong>Última actualización:</strong> {{ $balon->updated_at->translatedFormat('d/m/Y H:i') }}</li>
                    </ul>

                    @if ($balon->descripcion)
                        <p class="card-text text-muted small">{{ $balon->descripcion }}</p>
                    @endif

                    <div class="form-actions">
                        <form method="POST" action="{{ route('admin.balones.destroy', $balon) }}" class="d-inline"
                              data-confirm-submit data-confirm-action="delete"
                              data-confirm-title="¿Eliminar balón?"
                              data-confirm-text="Se eliminará también su imagen asociada.">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn--danger">
                                <i class="bi bi-trash me-1"></i> Eliminar
                            </button>
                        </form>
                        <span class="spacer"></span>
                        <a href="{{ route('admin.balones.edit', $balon) }}" class="btn btn--primary">
                            <i class="bi bi-pencil me-1"></i> Editar balón
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
