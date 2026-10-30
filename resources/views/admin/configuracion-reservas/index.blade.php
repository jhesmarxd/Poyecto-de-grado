@extends('layouts.app')

@section('title', 'Reglas de reservas y chatbot')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h1 class="h3 mb-1">Reglas de reservas y chatbot</h1>
            <p class="text-muted mb-0">Una sola configuración para chatbot, clientes, recepción y administración.</p>
        </div>
    </div>

    @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <form method="POST" action="{{ route('admin.configuracion-reservas.preview') }}" class="card shadow-sm mb-4">
        @csrf
        <div class="card-header"><strong>Configuración general y horario semanal</strong></div>
        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Anticipación máxima (días)</label>
                    <input class="form-control" type="number" min="1" max="365" name="anticipacion_maxima_dias" value="{{ old('anticipacion_maxima_dias', $configuracion['anticipacion_maxima_dias']) }}" required>
                </div>
                <div class="col-md-3 d-flex align-items-end"><label class="form-check mb-2"><input class="form-check-input" type="checkbox" name="permitir_hoy" value="1" @checked(old('permitir_hoy', $configuracion['permitir_hoy']))> Permitir hoy</label></div>
                <div class="col-md-3 d-flex align-items-end"><label class="form-check mb-2"><input class="form-check-input" type="checkbox" name="permitir_futuras" value="1" @checked(old('permitir_futuras', $configuracion['permitir_futuras']))> Permitir fechas futuras</label></div>
                <div class="col-md-3">
                    <label class="form-label d-block">Duraciones</label>
                    @foreach([60 => '60 min', 90 => '90 min', 120 => '120 min'] as $valor => $etiqueta)
                        <label class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="duraciones_permitidas[]" value="{{ $valor }}" @checked(in_array($valor, old('duraciones_permitidas', $configuracion['duraciones_permitidas'])))> {{ $etiqueta }}</label>
                    @endforeach
                </div>
            </div>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Día</th><th>Abierto</th><th>Desde</th><th>Hasta</th></tr></thead>
                    <tbody>
                    @foreach(\App\Models\Horario::DIAS as $dia => $nombre)
                        @php($regla = $configuracion['horarios'][$dia] ?? ['abierto' => true, 'hora_apertura' => '08:00', 'hora_cierre' => '22:00'])
                        <tr>
                            <td>{{ $nombre }}</td>
                            <td><input class="form-check-input" type="checkbox" name="horarios[{{ $dia }}][abierto]" value="1" @checked(old("horarios.$dia.abierto", $regla['abierto']))></td>
                            <td><input class="form-control" type="time" name="horarios[{{ $dia }}][hora_apertura]" value="{{ old("horarios.$dia.hora_apertura", $regla['hora_apertura']) }}"></td>
                            <td><input class="form-control" type="time" name="horarios[{{ $dia }}][hora_cierre]" value="{{ old("horarios.$dia.hora_cierre", $regla['hora_cierre']) }}"></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <label class="form-label">Motivo del cambio</label>
            <input class="form-control" name="motivo" maxlength="255" value="{{ old('motivo') }}" placeholder="Ej.: nuevo horario de temporada">
        </div>
        <div class="card-footer text-end"><button class="btn btn-primary">Revisar y guardar configuración</button></div>
    </form>

    <form method="POST" action="{{ route('admin.configuracion-reservas.exceptions.preview') }}" class="card shadow-sm mb-4">
        @csrf
        <div class="card-header"><strong>Agregar excepción o bloqueo</strong></div>
        <div class="card-body row g-3">
            <div class="col-md-3"><label class="form-label">Fecha</label><input class="form-control" type="date" name="fecha" min="{{ today()->toDateString() }}" required></div>
            <div class="col-md-3"><label class="form-label">Alcance</label><select class="form-select" name="cancha_id"><option value="">Todo el complejo</option>@foreach($canchas as $cancha)<option value="{{ $cancha->id }}">{{ $cancha->nombre }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">Tipo</label><select class="form-select" name="tipo">@foreach($tiposExcepcion as $tipo)<option value="{{ $tipo }}">{{ str($tipo)->replace('_', ' ')->title() }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">Motivo</label><input class="form-control" name="motivo" maxlength="255" required></div>
            <div class="col-md-3"><label class="form-label">Desde (si corresponde)</label><input class="form-control" type="time" name="hora_inicio"></div>
            <div class="col-md-3"><label class="form-label">Hasta (si corresponde)</label><input class="form-control" type="time" name="hora_fin"></div>
            <div class="col-md-6"><label class="form-label">Mensaje editable</label><textarea class="form-control" name="mensaje_cliente" rows="2" placeholder="Admite [fecha], [hora_inicio], [hora_fin] y [cancha]"></textarea></div>
        </div>
        <div class="card-footer text-end"><button class="btn btn-warning">Previsualizar impacto</button></div>
    </form>

    <div class="card shadow-sm">
        <div class="card-header"><strong>Excepciones próximas</strong></div>
        <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Fecha</th><th>Alcance</th><th>Tipo</th><th>Horario</th><th>Motivo</th></tr></thead><tbody>
        @forelse($excepciones as $excepcion)<tr><td>{{ $excepcion->fecha->format('d/m/Y') }}</td><td>{{ $excepcion->cancha?->nombre ?? 'Todo el complejo' }}</td><td>{{ str($excepcion->tipo)->replace('_', ' ')->title() }}</td><td>{{ $excepcion->hora_inicio ? substr($excepcion->hora_inicio,0,5).'–'.substr($excepcion->hora_fin,0,5) : 'Todo el día' }}</td><td>{{ $excepcion->motivo }}</td></tr>@empty<tr><td colspan="5" class="text-muted">No existen excepciones próximas.</td></tr>@endforelse
        </tbody></table></div>
    </div>

    @if($cambiosPendientes->isNotEmpty())
        <div class="alert alert-warning mt-4"><strong>Cambios pendientes de revisión:</strong>
            @foreach($cambiosPendientes as $cambio)<a class="ms-2" href="{{ route('admin.configuracion-reservas.review', $cambio) }}">#{{ $cambio->id }} ({{ $cambio->reservas_afectadas_count }} reservas)</a>@endforeach
        </div>
    @endif
</div>
@endsection
