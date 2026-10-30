{{--
    Mini-catálogo de balones.
    Variables: $balones (colección), $version (string), $modo ('info'|'seleccion'),
               $ocupados (array de ids reservados en ese día/hora), $cancha (solo en 'seleccion'),
               $vistaCompleta (muestra todas las categorías abiertas) y $compacto (tarjetas más pequeñas).
--}}
@php
    $modo = $modo ?? 'info';
    $ocupados = $ocupados ?? [];
    $vistaCompleta = $vistaCompleta ?? false;
    $compacto = $compacto ?? false;
    $economicos = $balones->where('tipo', \App\Models\Balon::TIPO_ECONOMICO)->values();
    $premiums = $balones->where('tipo', \App\Models\Balon::TIPO_PREMIUM)->values();
@endphp

<div class="balones-section {{ $modo === 'seleccion' ? 'booking-ball-catalog' : '' }} {{ $vistaCompleta ? 'balones-section--full' : '' }}">
    <div class="balones-section__head">
        <span class="client-kicker"><i class="bi bi-dribbble" aria-hidden="true"></i> Balones del recinto</span>
        <h3>{{ $modo === 'seleccion' ? '¿Prefieres usar uno de nuestros balones?' : 'Elige cómo quieres jugar' }}</h3>
        <p>
            @if ($modo === 'seleccion')
                Abre una categoría y selecciona un balón disponible.
            @elseif ($vistaCompleta)
                Revisa todos nuestros balones a simple vista y elige tu favorito.
            @else
                Puedes traer tu propio balón (tarifa de cancha) o usar uno de nuestro catálogo.
            @endif
        </p>
    </div>

    @if ($balones->isEmpty())
        <p class="text-center text-muted">Pronto publicaremos los balones disponibles.</p>
    @else
    <div class="{{ $vistaCompleta ? 'balones-catalog' : 'accordion' }}" id="balones-grid" data-version="{{ $version ?? '' }}">
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
            @if ($vistaCompleta)
                <section class="balones-category" aria-labelledby="heading-{{ $clave }}">
                    <header class="balones-category__header" id="heading-{{ $clave }}">
                        <i class="bi {{ $tipo === 'economico' ? 'bi-circle' : 'bi-stars' }}" aria-hidden="true"></i>
                        <span>{{ $titulo }}</span>
                        <span class="balones-category__count">{{ $lista->count() }}</span>
                    </header>
                    <div class="balones-category__body">
            @else
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading-{{ $clave }}">
                        <button class="accordion-button collapsed" type="button"
                                data-bs-toggle="collapse" data-bs-target="#collapse-{{ $clave }}"
                                aria-expanded="false" aria-controls="collapse-{{ $clave }}">
                            <i class="bi {{ $tipo === 'economico' ? 'bi-circle' : 'bi-stars' }}" aria-hidden="true"></i>
                            <span>{{ $titulo }}</span>
                            <span class="badge rounded-pill text-bg-secondary">{{ $lista->count() }}</span>
                        </button>
                    </h2>
                    <div id="collapse-{{ $clave }}" class="accordion-collapse collapse"
                         data-bs-parent="#balones-grid" aria-labelledby="heading-{{ $clave }}">
                        <div class="accordion-body">
            @endif
                            <div class="row g-3 justify-content-center">
                            @foreach ($lista as $balon)
                                @php($reservado = $modo === 'seleccion' && in_array($balon->id, $ocupados, true))
                                <div class="{{ $compacto ? 'col-12 col-sm-6 col-lg-4 col-xl-3' : 'col-md-6 col-xl-4' }}">
                                    @if ($modo === 'seleccion')
                                        <label class="balon-pick ball-choice h-100">
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
            @if ($vistaCompleta)
                    </div>
                </section>
            @else
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    </div>
    @endif
</div>
