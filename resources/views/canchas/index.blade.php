@extends(Auth::check() ? 'layouts.app' : 'layouts.public')

@section('title', 'Canchas disponibles')

@section('content')
    <div class="hero container-wide">
        <div class="hero-text">
            <span class="eyebrow">Wally · Canchas</span>
            <h1 class="hero-title">Nuestras <span class="accent">canchas</span></h1>
            <p class="hero-sub">Canchas cerradas (techadas) · Consulta precios, especificaciones y horarios disponibles.</p>
        </div>
    </div>

    <div class="container-wide pb-5">
        @if ($canchas->isEmpty())
            <div class="alert alert-info text-center">
                No hay canchas disponibles en este momento.
            </div>
        @else
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                @foreach ($canchas as $cancha)
                    <div class="col">
                        <div class="card h-100 shadow-sm overflow-hidden">
                            <div class="position-relative" style="height: 200px; background: rgba(var(--color-primario-rgb), 0.06);">
                                @if ($cancha->imagenUrl($version))
                                    <img src="{{ $cancha->imagenUrl($version) }}" alt="{{ $cancha->nombre }}"
                                         class="w-100 h-100" style="object-fit: cover;">
                                @else
                                    <div class="h-100 d-flex align-items-center justify-content-center text-muted">
                                        <i class="bi bi-image fs-1"></i>
                                    </div>
                                @endif
                                @if ($cancha->esCerrada())
                                    <span class="badge bg-info text-dark position-absolute top-0 start-0 m-2">
                                        <i class="bi bi-house-door-fill"></i> Cerrada (techada)
                                    </span>
                                @else
                                    <span class="badge bg-secondary position-absolute top-0 start-0 m-2">Campo abierto</span>
                                @endif
                            </div>

                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">{{ $cancha->nombre }}</h5>
                                <p class="card-text text-muted small">
                                    <i class="bi bi-geo-alt"></i>
                                    {{ $cancha->ubicacion ?? 'Ubicación no especificada' }}
                                </p>

                                <div class="mb-2">
                                    <span class="badge bg-light text-dark border me-1">Capacidad: {{ $cancha->capacidad }}</span>
                                    <span class="badge bg-light text-dark border">
                                        {{ $cancha->horarios_count }} franjas horarias
                                    </span>
                                </div>

                                <ul class="list-unstyled small text-muted mb-3">
                                    <li><i class="bi bi-grid-3x3-gap-fill me-1"></i> Malla <strong>{{ $cancha->color_malla ?? '—' }}</strong> · <strong>{{ $cancha->altura_malla ?? '—' }}</strong> de alto</li>
                                    <li><i class="bi bi-layers-fill me-1"></i> Piso: <strong>{{ $cancha->tipo_piso ?? '—' }}</strong></li>
                                    <li><i class="bi bi-ball-fill me-1"></i> Balón propio o prestado: <strong>Bs. {{ number_format($cancha->precio_por_hora, 2) }}</strong> · Premium: <strong>Bs. {{ number_format($balonPremium?->precio_por_hora ?? $cancha->precio_por_hora, 2) }}</strong></li>
                                </ul>

                                <p class="card-text">
                                    <strong class="fs-5">Bs. {{ number_format($cancha->precio_por_hora, 2) }}</strong>
                                    <span class="text-muted">/ hora</span>
                                </p>
                                <div class="mt-auto">
                                    <a href="{{ route('canchas.show', $cancha) }}" class="btn btn--primary w-100">Ver horarios</a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @include('balones.partials.cards', compact('balones', 'version'))
        @endif
    </div>
@endsection
