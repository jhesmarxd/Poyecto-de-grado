@extends('layouts.app')

@section('title', 'Confirmar reserva')

@section('content')
    <div class="client-page client-booking-page">
        <header class="client-page-header">
            <a class="client-back-link" href="{{ route('disponibilidad.index', ['cancha_id' => $cancha->id, 'fecha' => $fecha]) }}" aria-label="Volver a disponibilidad">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
            </a>
            <div class="client-page-header__icon" aria-hidden="true"><i class="bi bi-calendar2-check"></i></div>
            <div>
                <span class="client-kicker">Último paso</span>
                <h1>Confirma tu <span>reserva</span></h1>
                <p>Revisa la información, elige un balón y confirma cuando estés listo.</p>
            </div>
        </header>

        <div class="client-booking-layout">
            <aside class="booking-summary card" aria-labelledby="booking-summary-title">
                <div class="booking-summary__head">
                    <span class="booking-summary__icon" aria-hidden="true"><i class="bi bi-receipt"></i></span>
                    <div><span class="client-kicker">Resumen</span><h2 id="booking-summary-title">Tu reserva</h2></div>
                </div>
                <dl class="booking-summary__list">
                    <div><dt><i class="bi bi-grid-3x2" aria-hidden="true"></i> Cancha</dt><dd>{{ $cancha->nombre }}</dd></div>
                    <div><dt><i class="bi bi-calendar3" aria-hidden="true"></i> Fecha</dt><dd>{{ \Carbon\Carbon::parse($fecha)->translatedFormat('l d \d\e F \d\e Y') }}</dd></div>
                    <div><dt><i class="bi bi-clock" aria-hidden="true"></i> Horario</dt><dd>{{ \Carbon\Carbon::parse($horario->hora_inicio)->format('H:i') }} – {{ \Carbon\Carbon::parse($horario->hora_fin)->format('H:i') }}</dd></div>
                    <div><dt><i class="bi bi-cash-coin" aria-hidden="true"></i> Precio base</dt><dd>Bs. {{ number_format($cancha->precio_por_hora, 2) }} / hora</dd></div>
                </dl>
                <p class="booking-summary__note"><i class="bi bi-info-circle" aria-hidden="true"></i> El total puede cambiar según el balón que elijas.</p>
            </aside>

            <section class="booking-form card" aria-labelledby="booking-ball-title">
                <form method="POST" action="{{ route('reservas.store') }}">
                    @csrf
                    <input type="hidden" name="cancha_id" value="{{ $cancha->id }}">
                    <input type="hidden" name="horario_id" value="{{ $horario->id }}">
                    <input type="hidden" name="fecha" value="{{ $fecha }}">
                    <input type="hidden" name="balon_id" id="balon_id" value="">

                    <div class="booking-form__heading">
                        <span class="booking-form__step">1</span>
                        <div>
                            <span class="client-kicker">Elige cómo jugarás</span>
                            <h2 id="booking-ball-title">Selecciona un balón</h2>
                            <p>Puedes traer el tuyo o elegir uno disponible del catálogo.</p>
                        </div>
                    </div>

                    <div class="field booking-ball-field">
                        <div class="row g-3 mb-2">
                            <div class="col-md-6 col-xl-4">
                                <label class="balon-pick h-100 ball-choice ball-choice--own">
                                    <input type="radio" class="visually-hidden" name="balon_seleccion" id="balon-propio"
                                           value="" data-precio="{{ (float) $cancha->precio_por_hora }}" data-tipo="propio"
                                           checked>
                                    <div class="card balon-card h-100">
                                        <span class="ball-choice__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span>
                                        <span class="ball-choice__icon" aria-hidden="true"><i class="bi bi-person-walking"></i></span>
                                        <h3>Traigo mi propio balón</h3>
                                        <p>Solo pagas la tarifa base de la cancha.</p>
                                        <strong>Bs. {{ number_format($cancha->precio_por_hora, 2) }} / hora</strong>
                                    </div>
                                </label>
                            </div>
                        </div>

                        @include('balones.partials.cards', [
                            'balones' => $balones,
                            'version' => $version ?? '',
                            'modo' => 'seleccion',
                            'ocupados' => $balonesOcupados,
                        ])

                        @error('balon_id')
                            <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror

                        <div class="booking-total" role="status" aria-live="polite">
                            <span><i class="bi bi-wallet2" aria-hidden="true"></i> Total por hora</span>
                            <strong id="total-hora">Bs. {{ number_format($cancha->precio_por_hora, 2) }}</strong>
                        </div>
                    </div>

                    <div class="field booking-observations">
                        <label for="observaciones" class="field-label">Observaciones (opcional)</label>
                        <span class="field-help">Añade aquí cualquier información que debamos conocer.</span>
                        <textarea name="observaciones" id="observaciones" class="form-control" rows="3"
                                  placeholder="Ejemplo: jugaremos seis personas"></textarea>
                    </div>

                    <div class="form-actions booking-actions">
                        <a href="{{ route('disponibilidad.index', ['cancha_id' => $cancha->id, 'fecha' => $fecha]) }}"
                           class="btn btn--ghost"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver</a>
                        <span class="spacer"></span>
                        <button type="submit" class="btn btn--primary">
                            <i class="bi bi-check-circle" aria-hidden="true"></i> Confirmar reserva
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const total = document.getElementById('total-hora');
        const balonId = document.getElementById('balon_id');

        function recalcular() {
            const seleccionado = document.querySelector('input[name="balon_seleccion"]:checked');
            if (!seleccionado) return;
            const precio = parseFloat(seleccionado.dataset.precio);
            balonId.value = seleccionado.value || '';
            total.textContent = 'Bs. ' + precio.toFixed(2);
        }

        document.querySelectorAll('input[name="balon_seleccion"]').forEach(function (radio) {
            radio.addEventListener('change', recalcular);
        });

        window.actualizarTotalReserva = function () {
            recalcular();
        };

        recalcular();
    });
</script>
@endpush
