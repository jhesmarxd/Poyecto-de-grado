@extends(Auth::check() ? 'layouts.app' : 'layouts.public')

@section('title', 'Consultar disponibilidad')

@section('content')
    <div class="availability-page container-wide">
        <header class="client-page-header client-page-header--availability">
            <div class="client-page-header__icon" aria-hidden="true"><i class="bi bi-search"></i></div>
            <div>
                <span class="client-kicker">Reserva en pocos pasos</span>
                <h1>Consulta los <span>horarios disponibles</span></h1>
                <p>Elige una cancha y una fecha para encontrar el mejor horario para jugar.</p>
            </div>
        </header>

        <form method="GET" action="{{ route('disponibilidad.index') }}" class="availability-wizard card">
            <div class="availability-step">
                <span class="availability-step__number">1</span>
                <div class="availability-step__content">
                    <label for="cancha_id">Elige la cancha</label>
                    <small>Selecciona dónde quieres jugar.</small>
                    <div class="client-control-with-icon">
                        <i class="bi bi-grid-3x2" aria-hidden="true"></i>
                        <select name="cancha_id" id="cancha_id" class="form-select" required>
                            <option value="">Selecciona una cancha…</option>
                            @foreach ($canchas as $c)
                                <option value="{{ $c->id }}" @selected($cancha && $cancha->id === $c->id)>
                                    {{ $c->nombre }} — Bs. {{ number_format($c->precio_por_hora, 2) }}/h
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="availability-step">
                <span class="availability-step__number">2</span>
                <div class="availability-step__content">
                    <label for="fecha">Elige la fecha</label>
                    <small>Selecciona el día que quieres consultar.</small>
                    <div class="client-control-with-icon">
                        <i class="bi bi-calendar3" aria-hidden="true"></i>
                        <input type="date" name="fecha" id="fecha" class="form-control"
                               min="{{ now()->toDateString() }}" value="{{ $fecha }}" required>
                    </div>
                </div>
            </div>

            <div class="availability-step availability-step--submit">
                <span class="availability-step__number">3</span>
                <div class="availability-step__content">
                    <span class="availability-step__label">Consulta los horarios</span>
                    <small>Te mostraremos todas las horas libres.</small>
                    <button type="submit" class="btn btn--primary availability-submit">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <span>Consultar horarios</span>
                        <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </form>

        @if ($cancha)
            <section class="availability-results card" aria-labelledby="availability-results-title">
                <div class="availability-results__head">
                    <div class="client-page-header__icon" aria-hidden="true"><i class="bi bi-calendar2-week"></i></div>
                    <div>
                        <span class="client-kicker">Horarios libres</span>
                        <h2 id="availability-results-title">{{ $cancha->nombre }}</h2>
                        @if ($fecha)
                            <p>Horas libres para el <strong>{{ \Carbon\Carbon::parse($fecha)->translatedFormat('l d \d\e F \d\e Y') }}</strong>.</p>
                        @endif
                    </div>
                    <span class="availability-results__price">Bs. {{ number_format($cancha->precio_por_hora, 2) }} / hora</span>
                </div>

                @if ($fecha && $horariosLibres->isNotEmpty())
                    <p class="availability-results__help"><i class="bi bi-info-circle-fill" aria-hidden="true"></i> Toca un horario disponible para continuar con tu reserva.</p>
                    <div class="availability-slots">
                        @foreach ($horariosLibres as $horario)
                            <a href="{{ route('reservas.crear', ['cancha_id' => $cancha->id, 'horario_id' => $horario->id, 'fecha' => $fecha]) }}"
                               class="availability-slot">
                                <strong>{{ \Carbon\Carbon::parse($horario->hora_inicio)->format('H:i') }} – {{ \Carbon\Carbon::parse($horario->hora_fin)->format('H:i') }}</strong>
                                <span><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Disponible</span>
                                <i class="bi bi-chevron-right" aria-hidden="true"></i>
                            </a>
                        @endforeach
                    </div>
                @elseif ($fecha)
                    <div class="client-empty-state" role="status">
                        <i class="bi bi-calendar-x" aria-hidden="true"></i>
                        <strong>No hay horarios libres para esta fecha</strong>
                        <span>Prueba con otro día para encontrar más opciones.</span>
                    </div>
                @else
                    <div class="client-empty-state" role="status">
                        <i class="bi bi-calendar2-heart" aria-hidden="true"></i>
                        <strong>Selecciona una fecha</strong>
                        <span>Luego podrás ver y elegir los horarios disponibles.</span>
                    </div>
                @endif
            </section>
        @endif
    </div>
@endsection
