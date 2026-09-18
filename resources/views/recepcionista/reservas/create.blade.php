@extends('layouts.app')

@section('title', 'Registrar reserva')

@section('content')
@include('recepcionista._navigation')

    <div class="hero">
        <div class="hero-text">
            <span class="eyebrow">Agenda operativa</span>
            <h1 class="hero-title">Registrar <span class="accent">reserva</span></h1>
            <p class="hero-sub">Registrar una reserva presencial para un cliente.</p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('recepcionista.reservas.store') }}">
                @csrf

                <div class="mb-3">
                    <label for="user_id" class="form-label">Cliente</label>
                    <select name="user_id" id="user_id" class="form-select" required>
                        <option value="">Seleccionar cliente...</option>
                        @foreach ($clientes as $cliente)
                            <option value="{{ $cliente->id }}" {{ old('user_id') == $cliente->id ? 'selected' : '' }}>
                                {{ $cliente->name }} ({{ $cliente->email }})
                            </option>
                        @endforeach
                    </select>
                    @error('user_id') <div class="text-danger">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label for="cancha_id" class="form-label">Cancha</label>
                    <select name="cancha_id" id="cancha_id" class="form-select" required>
                        <option value="">Seleccionar cancha...</option>
                        @foreach ($canchas as $cancha)
                            <option value="{{ $cancha->id }}" {{ old('cancha_id') == $cancha->id ? 'selected' : '' }}>
                                {{ $cancha->nombre }} - Bs. {{ number_format($cancha->precio_por_hora, 2) }}/hora
                            </option>
                        @endforeach
                    </select>
                    @error('cancha_id') <div class="text-danger">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label for="fecha" class="form-label">Fecha</label>
                    <input type="date" name="fecha" id="fecha" class="form-control" value="{{ old('fecha', now()->toDateString()) }}" required>
                    @error('fecha') <div class="text-danger">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label for="horario_id" class="form-label">Horario</label>
                    <select name="horario_id" id="horario_id" class="form-select" required disabled>
                        <option value="">Selecciona primero cancha y fecha</option>
                    </select>
                    <div id="horario-ayuda" class="form-text">Se mostrarán únicamente las franjas disponibles.</div>
                    @error('horario_id') <div class="text-danger">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label for="balon_id" class="form-label">Balón (opcional)</label>
                    <select name="balon_id" id="balon_id" class="form-select">
                        <option value="">Trae tu propio balón</option>
                        @foreach (\App\Models\Balon::orderBy('tipo')->orderBy('nombre')->get() as $balon)
                            <option value="{{ $balon->id }}" {{ old('balon_id') == $balon->id ? 'selected' : '' }}>
                                {{ $balon->tipo === 'premium' ? 'Premium' : 'Económico' }} - {{ $balon->marca }} {{ $balon->nombre }} (Bs. {{ number_format($balon->precio_por_hora, 2) }})
                            </option>
                        @endforeach
                    </select>
                    @error('balon_id') <div class="text-danger">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label for="observaciones" class="form-label">Observaciones</label>
                    <textarea name="observaciones" id="observaciones" class="form-control" rows="3">{{ old('observaciones') }}</textarea>
                    @error('observaciones') <div class="text-danger">{{ $message }}</div> @enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Crear Reserva</button>
                    <a href="{{ route('recepcionista.reservas.hoy') }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const cancha = document.getElementById('cancha_id');
    const fecha = document.getElementById('fecha');
    const horario = document.getElementById('horario_id');
    const ayuda = document.getElementById('horario-ayuda');
    const endpoint = @json(route('recepcionista.reservas.horarios-disponibles'));
    const horarioAnterior = @json((string) old('horario_id', ''));

    async function cargarHorarios() {
        horario.disabled = true;
        horario.innerHTML = '<option value="">Consultando disponibilidad...</option>';

        if (!cancha.value || !fecha.value) {
            horario.innerHTML = '<option value="">Selecciona primero cancha y fecha</option>';
            ayuda.textContent = 'Se mostrarán únicamente las franjas disponibles.';
            return;
        }

        const parametros = new URLSearchParams({ cancha_id: cancha.value, fecha: fecha.value });

        try {
            const respuesta = await fetch(endpoint + '?' + parametros.toString(), {
                headers: { 'Accept': 'application/json' }
            });

            if (!respuesta.ok) {
                throw new Error('No se pudo consultar la disponibilidad.');
            }

            const franjas = await respuesta.json();
            horario.innerHTML = '<option value="">Seleccionar horario...</option>';

            franjas.forEach(function (franja) {
                const opcion = document.createElement('option');
                opcion.value = franja.id;
                opcion.textContent = franja.hora_inicio + ' - ' + franja.hora_fin;
                opcion.selected = String(franja.id) === horarioAnterior;
                horario.appendChild(opcion);
            });

            horario.disabled = franjas.length === 0;
            ayuda.textContent = franjas.length
                ? franjas.length + (franjas.length === 1 ? ' franja disponible.' : ' franjas disponibles.')
                : 'No quedan horarios disponibles para esa cancha y fecha.';
        } catch (error) {
            horario.innerHTML = '<option value="">No fue posible cargar horarios</option>';
            ayuda.textContent = error.message;
        }
    }

    cancha.addEventListener('change', cargarHorarios);
    fecha.addEventListener('change', cargarHorarios);
    cargarHorarios();
});
</script>
@endpush
