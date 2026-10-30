@extends(Auth::check() ? 'layouts.app' : 'layouts.public')

@section('title', $cancha->nombre)

@section('content')
    <div class="hero container-wide">
        <div class="hero-text">
            <span class="eyebrow">Wally · Canchas</span>
            <h1 class="hero-title">{{ $cancha->nombre }}</h1>
            <p class="hero-sub">{{ $cancha->ubicacion ?? 'Ubicación no especificada' }}</p>
        </div>
        <div class="hero-actions">
            @if ($cancha->estaDisponible())
                <span class="badge bg-success">Disponible</span>
            @else
                <span class="badge bg-warning text-dark">En mantenimiento</span>
            @endif
            @if ($cancha->esCerrada())
                <span class="badge bg-info text-dark"><i class="bi bi-house-door-fill"></i> Cancha cerrada (techada)</span>
            @endif
        </div>
    </div>

    <div class="container-wide pb-5">
        @if ($cancha->imagenUrl($version))
            <div class="rounded-4 overflow-hidden shadow-sm mb-4" style="max-height: 420px;">
                <img src="{{ $cancha->imagenUrl($version) }}" alt="{{ $cancha->nombre }}"
                     class="w-100" style="object-fit: cover; max-height: 420px;">
            </div>
        @endif

        <div class="grid">
            <div class="card col-7">
                <div class="card-head">
                    <div class="card-title-wrap">
                        <span class="eyebrow">Detalle</span>
                        <h3 class="card-title">Información de la cancha</h3>
                    </div>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-3">
                        <li class="mb-2">
                            <strong>Precio base:</strong> Bs. {{ number_format($cancha->precio_por_hora, 2) }} / hora
                        </li>
                        <li class="mb-2">
                            <strong>Tipo de recinto:</strong> Cerrada (techada, no campo abierto)
                        </li>
                        <li class="mb-2">
                            <strong>Color de la malla:</strong> {{ $cancha->color_malla ?? '—' }}
                        </li>
                        <li class="mb-2">
                            <strong>Altura de la malla:</strong> {{ $cancha->altura_malla ?? '—' }}
                        </li>
                        <li class="mb-2">
                            <strong>Tipo de piso:</strong> {{ $cancha->tipo_piso ?? '—' }}
                        </li>
                    </ul>

                    <div class="border rounded-3 p-3 mb-3" style="background: rgba(var(--color-primario-rgb), 0.06);">
                        <h6 class="fw-bold mb-2">Tarifas por hora (balón)</h6>
                        <ul class="list-unstyled small mb-0">
                            <li class="mb-1"><i class="bi bi-check-circle-fill text-success me-1"></i> Balón propio (traes tu balón): <strong>Bs. {{ number_format($cancha->precio_por_hora, 2) }}</strong></li>
                            <li class="mb-1"><i class="bi bi-check-circle-fill text-success me-1"></i> Balón prestado (económico): <strong>Bs. {{ number_format($cancha->precio_por_hora, 2) }}</strong></li>
                            <li><i class="bi bi-star-fill text-warning me-1"></i> Balón premium: <strong>Bs. {{ number_format($balonPremium?->precio_por_hora ?? $cancha->precio_por_hora, 2) }}</strong></li>
                        </ul>
                    </div>

                    @if ($cancha->descripcion)
                        <p class="card-text mb-0">{{ $cancha->descripcion }}</p>
                    @endif
                </div>
            </div>

            <div class="card col-5">
                <div class="card-head">
                    <div class="card-title-wrap">
                        <span class="eyebrow">Horarios</span>
                        <h3 class="card-title">Horarios de atención</h3>
                    </div>
                </div>
                <div class="card-body">
                    @if ($horarioAtencion)
                        <div class="border rounded-3 p-4 text-center" style="background: rgba(var(--color-primario-rgb), 0.06);">
                            <div class="fw-semibold mb-2">
                                {{ $horarioAtencion['dia_inicio_nombre'] }}
                                @if ($horarioAtencion['dia_inicio'] !== $horarioAtencion['dia_fin'])
                                    a {{ \Illuminate\Support\Str::lcfirst($horarioAtencion['dia_fin_nombre']) }}
                                @endif
                            </div>
                            <div class="fs-4 fw-bold text-primary">
                                {{ $horarioAtencion['hora_inicio'] }}–{{ $horarioAtencion['hora_fin'] }}
                            </div>
                        </div>
                        <p class="text-muted small mt-3 mb-0">
                            Horario general del complejo. Consulta las franjas disponibles al realizar tu reserva.
                        </p>
                    @else
                        <p class="text-muted mb-0">El horario general de atención aún no está configurado.</p>
                    @endif
                </div>
            </div>
        </div>

        @include('balones.partials.cards', compact('balones', 'version'))
    </div>
@endsection
