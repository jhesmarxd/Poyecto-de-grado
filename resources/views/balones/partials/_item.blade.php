{{-- Tarjeta de un balón del mini-catálogo — requiere $balon, $version, $reservado --}}
<div class="card balon-card h-100">
    @if (($modo ?? 'info') === 'seleccion')
        <span class="ball-choice__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span>
    @endif
    <div class="balon-foto">
        @if ($balon->imagenUrl($version ?? null))
            <img src="{{ $balon->imagenUrl($version ?? null) }}" alt="{{ $balon->marcaModelo() }}"
                 class="w-100 h-100 balon-img" data-id="{{ $balon->id }}" data-imagen="{{ $balon->imagenUrl() }}"
                 loading="lazy">
        @else
            <div class="h-100 d-flex flex-column align-items-center justify-content-center text-muted balon-sinfoto">
                <i class="bi bi-image display-4 mb-2"></i>
                <span class="small">Foto próximamente</span>
            </div>
        @endif
        @if ($reservado)
            <span class="badge text-bg-danger balon-unavailable">
                <i class="bi bi-calendar-x" aria-hidden="true"></i> No disponible
            </span>
        @endif
    </div>

    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <div class="me-2">
                @if ($balon->marca)
                    <span class="eyebrow d-block">{{ $balon->marca }}</span>
                @endif
                <h3 class="h6 fw-bold mb-0">{{ $balon->marcaModelo() }}</h3>
            </div>
            <span class="badge rounded-pill balon-precio {{ $balon->tipo === 'premium' ? 'text-bg-warning' : 'text-bg-success' }}"
                  data-id="{{ $balon->id }}" data-tipo="{{ $balon->tipo }}" data-precio="{{ (float) $balon->precio_por_hora }}">
                Bs. {{ number_format($balon->precio_por_hora, 2) }} / h
            </span>
        </div>

        <div class="d-flex flex-wrap gap-1 mb-2">
            @if ($balon->color)
                <span class="badge rounded-pill text-bg-light border">{{ $balon->color }}</span>
            @endif
            @if ($balon->calidad)
                <span class="badge rounded-pill text-bg-light border">{{ $balon->calidad }}</span>
            @endif
            @if ($balon->destacado)
                <span class="badge rounded-pill text-bg-info">Destacado</span>
            @endif
        </div>

        @if ($balon->descripcion)
            <p class="small text-muted mb-0">{{ $balon->descripcion }}</p>
        @endif
    </div>
</div>
