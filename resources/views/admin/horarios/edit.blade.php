@extends('layouts.app')

@section('title', 'Editar Horario')

@section('content')
    <div class="hero">
        <div class="hero-text">
            <span class="eyebrow">Horarios · Editar</span>
            <h1 class="hero-title">Editar <span class="accent">horario</span></h1>
            <p class="hero-sub">Franja horaria de {{ $cancha->nombre }}.</p>
        </div>
    </div>

    <div class="grid">
        <div class="card col-8">
            <div class="card-head">
                <div class="card-title-wrap">
                    <span class="eyebrow">{{ $cancha->nombre }}</span>
                    <h3 class="card-title">Editar franja</h3>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.horarios.update', [$cancha, $horario]) }}">
                @csrf
                @method('PUT')

                <div class="form-grid">
                    <div class="field">
                        <label for="dia_semana" class="field-label">Día de la semana</label>
                        <select id="dia_semana" name="dia_semana" class="form-select @error('dia_semana') is-invalid @enderror" required>
                            @foreach (\App\Models\Horario::DIAS as $valor => $nombre)
                                <option value="{{ $valor }}" @selected((int) old('dia_semana', $horario->dia_semana) === $valor)>{{ $nombre }}</option>
                            @endforeach
                        </select>
                        @error('dia_semana')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="hora_inicio" class="field-label">Hora de inicio</label>
                        <input id="hora_inicio" type="time" class="form-control @error('hora_inicio') is-invalid @enderror" name="hora_inicio" value="{{ old('hora_inicio', \Carbon\Carbon::parse($horario->hora_inicio)->format('H:i')) }}" required>
                        @error('hora_inicio')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="hora_fin" class="field-label">Hora de fin</label>
                        <input id="hora_fin" type="time" class="form-control @error('hora_fin') is-invalid @enderror" name="hora_fin" value="{{ old('hora_fin', \Carbon\Carbon::parse($horario->hora_fin)->format('H:i')) }}" required>
                        @error('hora_fin')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="field">
                        <label class="field-label">Disponible</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="disponible" name="disponible" value="1" @checked(old('disponible', $horario->disponible))>
                            <label class="form-check-label" for="disponible">Franja disponible para reservas</label>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="{{ route('admin.horarios.index', ['cancha_id' => $cancha->id]) }}" class="btn btn--ghost">Cancelar</a>
                    <span class="spacer"></span>
                    <button type="submit" class="btn btn--primary">Actualizar horario</button>
                </div>
            </form>
        </div>
    </div>
@endsection