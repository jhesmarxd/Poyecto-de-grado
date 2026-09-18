@php
    $calendarRole = $calendarRole ?? 'cliente';
    $showFilters = in_array($calendarRole, ['administrador', 'recepcionista'], true);
@endphp

<section class="reservation-calendar-shell"
         data-reservation-calendar
         data-role="{{ $calendarRole }}"
         data-events-url="{{ $eventsUrl }}"
         data-time-zone="America/La_Paz">
    @if ($showFilters)
        <div class="calendar-filters" aria-label="Filtros del calendario">
            <div class="calendar-filter">
                <label for="calendar-cancha-{{ $calendarRole }}" class="form-label">Cancha</label>
                <select id="calendar-cancha-{{ $calendarRole }}" class="form-select" data-calendar-filter="cancha_id">
                    <option value="">Todas las canchas</option>
                    @foreach ($canchas as $cancha)
                        <option value="{{ $cancha->id }}">{{ $cancha->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="calendar-filter">
                <label for="calendar-estado-{{ $calendarRole }}" class="form-label">Estado</label>
                <select id="calendar-estado-{{ $calendarRole }}" class="form-select" data-calendar-filter="estado">
                    <option value="">Todos los estados</option>
                    @foreach (\App\Models\Reserva::ESTADOS as $valor => $etiqueta)
                        <option value="{{ $valor }}">{{ $etiqueta }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    @endif

    <div class="card reservation-calendar-card">
        <div class="calendar-status" data-calendar-loading role="status" aria-live="polite">
            Cargando reservas…
        </div>
        <div class="calendar-empty" data-calendar-empty hidden>
            No hay reservas para el periodo y los filtros seleccionados.
        </div>
        <div class="reservation-calendar" data-calendar-target data-fc></div>
        <div class="calendar-legend" aria-label="Leyenda de estados">
            <span><i class="is-pending" aria-hidden="true"></i>Pendiente</span>
            <span><i class="is-confirmed" aria-hidden="true"></i>Confirmada</span>
            <span><i class="is-completed" aria-hidden="true"></i>Completada</span>
            <span><i class="is-cancelled" aria-hidden="true"></i>Cancelada</span>
        </div>
    </div>
</section>
