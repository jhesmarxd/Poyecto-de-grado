{{--
    Mini-catálogo desplegable de balones.
    Variables: $balones (colección), $version (string), $modo ('info'|'seleccion'),
               $ocupados (array de ids reservados en ese día/hora), $cancha (solo en 'seleccion').
--}}
@php
    $modo = $modo ?? 'info';
    $ocupados = $ocupados ?? [];
    $economicos = $balones->where('tipo', \App\Models\Balon::TIPO_ECONOMICO)->values();
    $premiums = $balones->where('tipo', \App\Models\Balon::TIPO_PREMIUM)->values();
@endphp

<style>
    .balon-pick { display: block; cursor: pointer; }
    .balon-pick .balon-card { transition: border-color .15s ease, box-shadow .15s ease; border-width: 2px; }
    .balon-pick input:checked ~ .balon-card {
        border-color: var(--bs-success);
        box-shadow: 0 0 0 3px rgba(var(--color-primario-rgb), .22);
    }
    .balon-pick input:disabled { cursor: not-allowed; }
    .balon-pick input:disabled ~ .balon-card { opacity: .5; }
    .balon-foto { position: relative; }
</style>

<div class="balones-section mt-5">
    <div class="text-center mb-4">
        <span class="badge rounded-pill px-3 py-2 fw-semibold"
              style="background: rgba(var(--color-primario-rgb), 0.12); color: var(--color-primario)">Balones</span>
        <h2 class="display-6 fw-bold mt-3 mb-2">Elige cómo quieres jugar</h2>
        <p class="fs-5 text-muted mb-0">
            @if ($modo === 'seleccion')
                Despliega cada sección y elige el balón exacto con el que jugarás. Si un balón ya está reservado en este horario, quedará marcado como no disponible.
            @else
                Puedes traer tu propio balón (tarifa de cancha) o usar uno de nuestro catálogo.
            @endif
        </p>
    </div>

    @if ($balones->isEmpty())
        <p class="text-center text-muted">Pronto publicaremos los balones disponibles.</p>
    @else
    <div class="accordion" id="balones-grid" data-version="{{ $version ?? '' }}">
        @foreach (['economico' => $economicos, 'premium' => $premiums] as $tipo => $lista)
            @if ($lista->isEmpty())
                @continue
            @endif
            @php
                $clave = $tipo === 'economico' ? 'economicos' : 'premiums';
                $titulo = $tipo === 'economico'
                    ? 'Balones económicos que presta la dueña'
                    : 'Balones premium (mejor calidad)';
            @endphp
            <div class="accordion-item border-0 rounded-4 overflow-hidden mb-3 shadow-sm">
                <h2 class="accordion-header" id="heading-{{ $clave }}">
                    <button class="accordion-button collapsed fw-semibold" type="button"
                            data-bs-toggle="collapse" data-bs-target="#collapse-{{ $clave }}"
                            aria-expanded="false" aria-controls="collapse-{{ $clave }}">
                        <span class="me-2">{{ $titulo }}</span>
                        <span class="badge rounded-pill text-bg-secondary">{{ $lista->count() }}</span>
                    </button>
                </h2>
                <div id="collapse-{{ $clave }}" class="accordion-collapse collapse"
                     data-bs-parent="#balones-grid" aria-labelledby="heading-{{ $clave }}">
                    <div class="accordion-body">
                        <div class="row g-4">
                            @foreach ($lista as $balon)
                                @php($reservado = $modo === 'seleccion' && in_array($balon->id, $ocupados, true))
                                <div class="col-md-6 col-lg-3">
                                    @if ($modo === 'seleccion')
                                        <label class="balon-pick h-100">
                                            <input type="radio" class="visually-hidden" name="balon_seleccion"
                                                   value="{{ $balon->id }}"
                                                   data-precio="{{ (float) $balon->precio_por_hora }}"
                                                   data-tipo="{{ $balon->tipo }}"
                                                   data-marca="{{ $balon->marca }}"
                                                   @disabled(in_array($balon->id, $ocupados, true))>
                                            @include('balones.partials._item')
                                        </label>
                                    @else
                                        @include('balones.partials._item')
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    @endif
</div>