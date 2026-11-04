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
            <input type="hidden" name="consultar" value="1">
            <div class="availability-step">
                <span class="availability-step__number">1</span>
                <div class="availability-step__content">
                    <span class="availability-step__label">Elige la cancha</span>
                    <small>Selecciona dónde quieres jugar.</small>
                    <x-cancha-picker :canchas="$canchas" :selected="$cancha?->id" />
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
                    <small>Te mostraremos las franjas libres y ocupadas.</small>
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
                        <span class="client-kicker">Horarios del día</span>
                        <h2 id="availability-results-title">{{ $cancha->nombre }}</h2>
                        @if ($fecha)
                            <p>Franjas para el <strong>{{ \Carbon\Carbon::parse($fecha)->translatedFormat('l d \d\e F \d\e Y') }}</strong>.</p>
                        @endif
                    </div>
                    <span class="availability-results__price">Bs. {{ number_format($cancha->precio_por_hora, 2) }} / hora</span>
                </div>

                @if ($fecha && $franjas->isNotEmpty())
                    <p class="availability-results__help"><i class="bi bi-info-circle-fill" aria-hidden="true"></i> Selecciona franjas libres consecutivas de 60 a 180 minutos.</p>
                    <form method="GET" action="{{ route('reservas.crear') }}" data-availability-selection data-durations="{{ implode(',', $duraciones) }}">
                        <input type="hidden" name="cancha_id" value="{{ $cancha->id }}">
                        <input type="hidden" name="fecha" value="{{ $fecha }}">
                        <input type="hidden" name="horario_id" value="" data-start-id>
                        <input type="hidden" name="hora_fin" value="" data-end-time>
                        <div class="availability-slots">
                            @foreach ($franjas as $item)
                                @php($horario = $item['horario'])
                                <button type="button" class="availability-slot @unless($item['disponible']) is-unavailable @endunless"
                                        data-slot data-id="{{ $horario->id }}" data-start="{{ substr($horario->hora_inicio, 0, 5) }}"
                                        data-end="{{ substr($horario->hora_fin, 0, 5) }}" @disabled(! $item['disponible']) aria-pressed="false">
                                    <strong>{{ substr($horario->hora_inicio, 0, 5) }} – {{ substr($horario->hora_fin, 0, 5) }}</strong>
                                    <span>{{ $item['disponible'] ? 'Disponible' : 'No disponible' }}</span>
                                </button>
                            @endforeach
                        </div>
                        <div class="availability-selection-actions">
                            <span data-selection-summary>Selecciona al menos 60 minutos.</span>
                            <button type="submit" class="btn btn--primary" data-reserve disabled>Reservar</button>
                        </div>
                    </form>
                @elseif ($fecha)
                    <div class="client-empty-state" role="status">
                        <i class="bi bi-calendar-x" aria-hidden="true"></i>
                        <strong>No hay franjas configuradas para esta fecha</strong>
                        <span>Puede ser un día cerrado. Prueba con otra fecha.</span>
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
