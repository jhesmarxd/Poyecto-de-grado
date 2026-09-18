@extends('layouts.app')

@section('title', 'Confirmar reserva')

@section('content')
    <div class="hero container-wide">
        <div class="hero-text">
            <span class="eyebrow">Reservas · Confirmar</span>
            <h1 class="hero-title">Confirmar <span class="accent">reserva</span></h1>
            <p class="hero-sub">Revisa los datos, elige el balón con el que jugarás y confirma.</p>
        </div>
    </div>

    <div class="container-wide">
        <div class="grid">
            <div class="card col-12">
                <div class="card-head">
                    <div class="card-title-wrap">
                        <span class="eyebrow">Detalle</span>
                        <h3 class="card-title">Tu reserva</h3>
                    </div>
                </div>

                <dl class="row mb-4">
                    <dt class="col-sm-4">Cancha</dt>
                    <dd class="col-sm-8">{{ $cancha->nombre }}</dd>

                    <dt class="col-sm-4">Fecha</dt>
                    <dd class="col-sm-8">
                        {{ \Carbon\Carbon::parse($fecha)->translatedFormat('l d \d\e F \d\e Y') }}
                    </dd>

                    <dt class="col-sm-4">Hora</dt>
                    <dd class="col-sm-8">
                        {{ \Carbon\Carbon::parse($horario->hora_inicio)->format('H:i') }}
                        – {{ \Carbon\Carbon::parse($horario->hora_fin)->format('H:i') }}
                    </dd>

                    <dt class="col-sm-4">Precio base</dt>
                    <dd class="col-sm-8">Bs. {{ number_format($cancha->precio_por_hora, 2) }} / hora
                        <span class="small text-muted">(si traes tu propio balón)</span></dd>
                </dl>

                <form method="POST" action="{{ route('reservas.store') }}">
                    @csrf
                    <input type="hidden" name="cancha_id" value="{{ $cancha->id }}">
                    <input type="hidden" name="horario_id" value="{{ $horario->id }}">
                    <input type="hidden" name="fecha" value="{{ $fecha }}">
                    <input type="hidden" name="balon_id" id="balon_id" value="">

                    <div class="field mb-3">
                        <label class="field-label">Elige el balón con el que jugarás</label>

                        <div class="row g-4 mb-2">
                            <div class="col-md-6 col-lg-3">
                                <label class="balon-pick h-100">
                                    <input type="radio" class="visually-hidden" name="balon_seleccion" id="balon-propio"
                                           value="" data-precio="{{ (float) $cancha->precio_por_hora }}" data-tipo="propio"
                                           checked>
                                    <div class="card balon-card h-100 border-0 shadow-sm rounded-4">
                                        <div class="card-body d-flex flex-column align-items-center justify-content-center text-center p-3"
                                             style="min-height: 180px; background: rgba(var(--color-primario-rgb), 0.05);">
                                            <i class="bi bi-person-walking display-4 mb-2 text-muted"></i>
                                            <h3 class="h6 fw-bold mb-1">Traigo mi propio balón</h3>
                                            <p class="small text-muted mb-2">Tarifa base de la cancha</p>
                                            <span class="badge rounded-pill text-bg-success">
                                                Bs. {{ number_format($cancha->precio_por_hora, 2) }} / h
                                            </span>
                                        </div>
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

                        <div class="alert alert-info mt-3 mb-0" role="alert">
                            <strong>Total por hora:</strong> <span id="total-hora">Bs. {{ number_format($cancha->precio_por_hora, 2) }}</span>
                            <span class="small text-muted">(el monto de la reserva se calcula con la tarifa del balón elegido)</span>
                        </div>
                    </div>

                    <div class="field mb-3">
                        <label for="observaciones" class="field-label">Observaciones (opcional)</label>
                        <textarea name="observaciones" id="observaciones" class="form-control" rows="2"
                                  placeholder="Ej.: queremos jugar con 6 personas…"></textarea>
                    </div>

                    <div class="form-actions">
                        <a href="{{ route('disponibilidad.index', ['cancha_id' => $cancha->id, 'fecha' => $fecha]) }}"
                           class="btn btn--ghost">Volver</a>
                        <span class="spacer"></span>
                        <button type="submit" class="btn btn--primary">Confirmar reserva</button>
                    </div>
                </form>
            </div>
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
