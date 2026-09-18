@extends('layouts.app')

@section('title', 'Google Calendar')

@section('content')
    <div class="hero">
        <div class="hero-text">
            <span class="eyebrow">Configuración e integraciones</span>
            <h1 class="hero-title">Google <span class="accent">Calendar</span></h1>
            <p class="hero-sub">Conecta el calendario empresarial y controla la sincronización de reservas.</p>
        </div>
    </div>

    @error('google_calendar')
        <div class="alert alert-danger" role="alert">{{ $message }}</div>
    @enderror

    @if (! $globallyEnabled)
        <div class="alert alert-warning">
            La sincronización está protegida y no realizará llamadas a Google mientras
            <code>GOOGLE_CALENDAR_ENABLED=false</code>.
        </div>
    @endif

    <div class="grid">
        <div class="card col-7">
            <div class="card-head">
                <div class="card-title-wrap">
                    <span class="eyebrow">Conexión OAuth</span>
                    <h3 class="card-title">Calendario empresarial</h3>
                </div>
                @if ($connection)
                    <span class="badge {{ $connection->enabled ? 'bg-success' : 'bg-secondary' }}">
                        {{ $connection->enabled ? 'Conectado' : 'Pausado' }}
                    </span>
                @endif
            </div>

            @if (! $configured)
                <p class="text-muted">Faltan las variables OAuth de Calendar en <code>.env</code>.</p>
            @elseif (! $connection)
                <p class="text-muted">La autorización solo puede realizarla un administrador.</p>
                <a href="{{ route('admin.integraciones.google-calendar.connect') }}" class="btn btn--primary">
                    Conectar Google Calendar
                </a>
            @else
                <dl class="row mb-4">
                    <dt class="col-sm-4">Cuenta autorizada</dt>
                    <dd class="col-sm-8">{{ $connection->account_email }}</dd>
                    <dt class="col-sm-4">Conectado el</dt>
                    <dd class="col-sm-8">{{ $connection->connected_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    <dt class="col-sm-4">Última sincronización</dt>
                    <dd class="col-sm-8">{{ $connection->last_synced_at?->format('d/m/Y H:i') ?? 'Todavía no ejecutada' }}</dd>
                </dl>

                <form method="POST" action="{{ route('admin.integraciones.google-calendar.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="field mb-3">
                        <label for="calendar_id" class="field-label">ID del calendario</label>
                        <input id="calendar_id" name="calendar_id" class="form-control"
                               value="{{ old('calendar_id', $connection->calendar_id) }}" required>
                        <span class="field-help">Usa <code>primary</code> para el calendario principal. Si ya existen eventos externos, el ID queda protegido para evitar desincronizarlos.</span>
                    </div>
                    <div class="form-check mb-3">
                        <input type="hidden" name="enabled" value="0">
                        <input id="calendar_enabled" type="checkbox" name="enabled" value="1"
                               class="form-check-input" @checked($connection->enabled)>
                        <label for="calendar_enabled" class="form-check-label">Habilitar esta conexión</label>
                    </div>
                    <button type="submit" class="btn btn--primary">Guardar configuración</button>
                </form>

                <div class="d-flex gap-2 flex-wrap mt-4">
                    <a href="{{ route('admin.integraciones.google-calendar.connect') }}" class="btn btn--ghost">Reautorizar</a>
                    <form method="POST" action="{{ route('admin.integraciones.google-calendar.disconnect') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger">Desconectar localmente</button>
                    </form>
                </div>
            @endif
        </div>

        <div class="card col-5">
            <div class="card-head">
                <div class="card-title-wrap">
                    <span class="eyebrow">Estado local</span>
                    <h3 class="card-title">Sincronización</h3>
                </div>
            </div>

            <div class="d-grid gap-2 mb-4">
                <div class="d-flex justify-content-between"><span>Sincronizadas</span><strong>{{ $syncCounts['synced'] ?? 0 }}</strong></div>
                <div class="d-flex justify-content-between"><span>Pendientes</span><strong>{{ $syncCounts['pending'] ?? 0 }}</strong></div>
                <div class="d-flex justify-content-between"><span>Con error</span><strong>{{ $syncCounts['error'] ?? 0 }}</strong></div>
                <div class="d-flex justify-content-between"><span>Canceladas sincronizadas</span><strong>{{ $syncCounts['cancelled'] ?? 0 }}</strong></div>
                <div class="d-flex justify-content-between"><span>Eliminadas</span><strong>{{ $syncCounts['deleted'] ?? 0 }}</strong></div>
            </div>

            <form method="POST" action="{{ route('admin.integraciones.google-calendar.sync') }}">
                @csrf
                <button type="submit" class="btn btn--primary"
                        @disabled(! $globallyEnabled || ! $connection || ! $connection->enabled)>
                    Sincronizar reservas futuras
                </button>
            </form>
            <form method="POST" action="{{ route('admin.integraciones.google-calendar.verify') }}" class="mt-2">
                @csrf
                <button type="submit" class="btn btn--ghost"
                        @disabled(! $globallyEnabled || ! $connection || ! $connection->enabled)>
                    Verificar conexión y permisos
                </button>
            </form>
            <p class="field-help mt-2 mb-0">El botón solo coloca trabajos en la cola; la pantalla no consulta Google automáticamente.</p>
        </div>
    </div>
@endsection
