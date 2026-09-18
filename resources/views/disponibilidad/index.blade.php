@extends(Auth::check() ? 'layouts.app' : 'layouts.public')

@section('title', 'Consultar disponibilidad')

@section('content')
    <div class="hero container-wide">
        <div class="hero-text">
            <span class="eyebrow">Wally · Reservas</span>
            <h1 class="hero-title">Consultar <span class="accent">disponibilidad</span></h1>
            <p class="hero-sub">Elige una cancha y una fecha para ver las horas libres disponibles.</p>
        </div>
    </div>

    <div class="container-wide pb-5">
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('disponibilidad.index') }}" class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label for="cancha_id" class="form-label">Cancha</label>
                        <select name="cancha_id" id="cancha_id" class="form-select" required>
                            <option value="">Selecciona una cancha…</option>
                            @foreach ($canchas as $c)
                                <option value="{{ $c->id }}" @selected($cancha && $cancha->id === $c->id)>
                                    {{ $c->nombre }} — Bs. {{ number_format($c->precio_por_hora, 2) }}/h
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label for="fecha" class="form-label">Fecha</label>
                        <input type="date" name="fecha" id="fecha" class="form-control"
                               min="{{ now()->toDateString() }}"
                               value="{{ $fecha }}" required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn--primary w-100">Consultar</button>
                    </div>
                </form>
            </div>
        </div>

        @if ($cancha)
            <div class="card">
                <div class="card-head">
                    <div class="card-title-wrap">
                        <span class="eyebrow">Horarios libres</span>
                        <h3 class="card-title">{{ $cancha->nombre }} · Bs. {{ number_format($cancha->precio_por_hora, 2) }} / hora</h3>
                    </div>
                </div>
                <div class="card-body">
                    @if ($fecha && $horariosLibres->isNotEmpty())
                        <p class="mb-3">
                            Horas libres para el
                            <strong>{{ \Carbon\Carbon::parse($fecha)->translatedFormat('l d \d\e F \d\e Y') }}</strong>:
                        </p>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($horariosLibres as $horario)
                                <a href="{{ route('reservas.crear', ['cancha_id' => $cancha->id, 'horario_id' => $horario->id, 'fecha' => $fecha]) }}"
                                   class="btn btn--outline-primary">
                                    {{ \Carbon\Carbon::parse($horario->hora_inicio)->format('H:i') }}
                                    – {{ \Carbon\Carbon::parse($horario->hora_fin)->format('H:i') }}
                                </a>
                            @endforeach
                        </div>
                    @elseif ($fecha)
                        <div class="alert alert-info mb-0">
                            No hay horarios libres para esta cancha en la fecha seleccionada.
                        </div>
                    @else
                        <p class="text-muted mb-0">Selecciona una cancha y una fecha para ver los horarios.</p>
                    @endif
                </div>
            </div>
        @endif
    </div>
@endsection
