@extends('layouts.app')

@section('title', 'Preferencias del sistema')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-0">Preferencias del sistema</h1>
            <p class="text-muted mb-0">Personaliza la apariencia y el horario informativo del complejo.</p>
        </div>
        <a href="{{ url('/') }}" target="_blank" class="btn btn-outline-secondary btn-sm">Ver el sitio</a>
    </div>

    <div class="row g-3">
        {{-- Paletas sugeridas --}}
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">Paletas sugeridas</div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @foreach ($paletas as $nombre => [$p, $a, $o])
                            <button type="button" class="btn btn-outline-secondary text-start paleta-btn"
                                    data-primario="{{ $p }}" data-acento="{{ $a }}" data-oscuro="{{ $o }}">
                                <span class="d-inline-flex align-items-center gap-2">
                                    <span class="rounded-circle d-inline-block" style="width:20px;height:20px;background:{{ $p }}"></span>
                                    <span class="rounded-circle d-inline-block" style="width:20px;height:20px;background:{{ $a }}"></span>
                                    <span class="rounded-circle d-inline-block" style="width:20px;height:20px;background:{{ $o }}"></span>
                                    <span>{{ $nombre }}</span>
                                </span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Formulario de colores --}}
        <div class="col-lg-8">
            <form method="POST" action="{{ route('admin.ajustes.update') }}">
                @csrf
                @method('PUT')

                <div class="card shadow-sm">
                    <div class="card-header">Colores del tema</div>
                    <div class="card-body">
                        <div class="row g-3">
                            @foreach ([
                                'color_primario' => ['Color principal', 'Navbar, sección hero, botones y etiquetas.'],
                                'color_acento' => ['Color de acento', 'Botones de acción, detalles y elementos destacados.'],
                                'color_oscuro' => ['Color oscuro', 'Pie de página y sección final de llamada a la acción.'],
                            ] as $clave => [$titulo, $descripcion])
                                <div class="col-md-6">
                                    <label for="{{ $clave }}" class="form-label fw-semibold">{{ $titulo }}</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" id="{{ $clave }}"
                                               name="{{ $clave }}" value="{{ $colores[$clave] }}"
                                               oninput="sincronizar('{{ $clave }}', this.value)">
                                        <input type="text" class="form-control color-hex" id="texto-{{ $clave }}"
                                               value="{{ $colores[$clave] }}" maxlength="7" pattern="#[0-9a-fA-F]{6}"
                                               onchange="sincronizar('{{ $clave }}', this.value)">
                                    </div>
                                    <div class="form-text">{{ $descripcion }}</div>
                                    @error($clave)
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endforeach
                        </div>

                        <hr>

                        <h6 class="fw-bold mb-3">Vista previa</h6>
                        <div class="rounded-4 overflow-hidden text-white" id="preview-hero"
                             style="background: linear-gradient(135deg, var(--color-primario), var(--color-primario-oscuro));">
                            <div class="p-4 p-md-5">
                                <span class="badge mb-2" id="preview-badge" style="background: var(--color-acento); color:#111">🎾 Reserva online 24/7</span>
                                <h4 class="fw-bold mb-1">Reserva tu cancha de Wally en minutos</h4>
                                <p class="mb-3" style="color: rgba(255,255,255,.8)">Consulta la disponibilidad en tiempo real y confirma tu reserva sin llamadas.</p>
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="button" class="btn btn-sm fw-semibold" id="preview-boton" style="background: var(--color-acento); color:#111">Consultar disponibilidad</button>
                                    <button type="button" class="btn btn-sm btn-outline-light">Ver canchas</button>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-3 overflow-hidden mt-2" id="preview-footer" style="background: var(--color-oscuro);">
                            <div class="p-3 text-center small" style="color: rgba(255,255,255,.75)">Pie de página con el color oscuro</div>
                        </div>

                        <hr class="my-4">

                        <h6 class="fw-bold mb-1">Horario general de atención</h6>
                        <p class="text-muted small mb-3">
                            Este horario es únicamente informativo. Las reservas continuarán utilizando las franjas configuradas para cada cancha.
                        </p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="horario_atencion_dia_inicio" class="form-label">Desde el día</label>
                                <select id="horario_atencion_dia_inicio" name="horario_atencion_dia_inicio"
                                        class="form-select @error('horario_atencion_dia_inicio') is-invalid @enderror" required>
                                    @foreach (\App\Models\Horario::DIAS as $numero => $nombre)
                                        <option value="{{ $numero }}" @selected((int) old('horario_atencion_dia_inicio', $horarioAtencion['dia_inicio'] ?? 0) === $numero)>
                                            {{ $nombre }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('horario_atencion_dia_inicio')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="horario_atencion_dia_fin" class="form-label">Hasta el día</label>
                                <select id="horario_atencion_dia_fin" name="horario_atencion_dia_fin"
                                        class="form-select @error('horario_atencion_dia_fin') is-invalid @enderror" required>
                                    @foreach (\App\Models\Horario::DIAS as $numero => $nombre)
                                        <option value="{{ $numero }}" @selected((int) old('horario_atencion_dia_fin', $horarioAtencion['dia_fin'] ?? 0) === $numero)>
                                            {{ $nombre }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('horario_atencion_dia_fin')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="horario_atencion_hora_inicio" class="form-label">Hora de apertura</label>
                                <input id="horario_atencion_hora_inicio" type="time" name="horario_atencion_hora_inicio"
                                       class="form-control @error('horario_atencion_hora_inicio') is-invalid @enderror"
                                       value="{{ old('horario_atencion_hora_inicio', $horarioAtencion['hora_inicio'] ?? '') }}" required>
                                @error('horario_atencion_hora_inicio')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="horario_atencion_hora_fin" class="form-label">Hora de cierre</label>
                                <input id="horario_atencion_hora_fin" type="time" name="horario_atencion_hora_fin"
                                       class="form-control @error('horario_atencion_hora_fin') is-invalid @enderror"
                                       value="{{ old('horario_atencion_hora_fin', $horarioAtencion['hora_fin'] ?? '') }}" required>
                                @error('horario_atencion_hora_fin')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Guardar ajustes</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function oscurecer(hex, factor) {
        const n = parseInt(hex.slice(1), 16);
        const r = Math.round(((n >> 16) & 255) * (1 - factor));
        const g = Math.round(((n >> 8) & 255) * (1 - factor));
        const b = Math.round((n & 255) * (1 - factor));
        return '#' + [r, g, b].map(v => v.toString(16).padStart(2, '0')).join('');
    }

    function sincronizar(clave, valor) {
        const valido = /^#[0-9a-fA-F]{6}$/.test(valor);
        if (!valido) return;

        document.getElementById(clave).value = valor;
        document.getElementById('texto-' + clave).value = valor;
        actualizarVistaPrevia();
    }

    function actualizarVistaPrevia() {
        const hero = document.getElementById('preview-hero');
        const primario = document.getElementById('color_primario').value;
        const acento = document.getElementById('color_acento').value;
        const oscuro = document.getElementById('color_oscuro').value;

        hero.style.setProperty('--color-primario', primario);
        hero.style.setProperty('--color-primario-oscuro', oscurecer(primario, 0.18));
        hero.style.setProperty('--color-acento', acento);
        document.getElementById('preview-footer').style.setProperty('--color-oscuro', oscuro);
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.paleta-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                ['color_primario', 'color_acento', 'color_oscuro'].forEach(clave => {
                    const dato = { color_primario: 'primario', color_acento: 'acento', color_oscuro: 'oscuro' }[clave];
                    sincronizar(clave, btn.dataset[dato]);
                });
            });
        });
        actualizarVistaPrevia();
    });
</script>
@endpush
