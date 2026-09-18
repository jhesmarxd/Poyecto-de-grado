@extends('layouts.app')

@section('title', 'Detalle de conversación')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Detalle de conversación</span>
                    <a href="{{ route('admin.chatbot.conversaciones') }}" class="btn btn-outline-secondary btn-sm">Volver</a>
                </div>

                <div class="card-body">
                    <dl class="row mb-3">
                        <dt class="col-sm-3">Usuario</dt>
                        <dd class="col-sm-9">{{ $conversacion->usuario?->name ?? 'Visitante (sin sesión)' }}</dd>

                        <dt class="col-sm-3">Correo</dt>
                        <dd class="col-sm-9">{{ $conversacion->usuario?->email ?? '—' }}</dd>

                        <dt class="col-sm-3">Iniciada</dt>
                        <dd class="col-sm-9">{{ $conversacion->created_at->translatedFormat('d/m/Y H:i') }}</dd>

                        <dt class="col-sm-3">Contexto</dt>
                        <dd class="col-sm-9 small text-muted">
                            {{ $conversacion->contexto ? json_encode($conversacion->contexto, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '—' }}
                        </dd>
                    </dl>

                    <hr>

                    <div class="chat-log">
                        @foreach ($conversacion->mensajes as $mensaje)
                            <div class="d-flex mb-2 {{ $mensaje->emisor === 'bot' ? '' : 'justify-content-end' }}">
                                <div class="rounded-3 px-3 py-2 {{ $mensaje->emisor === 'bot' ? 'bg-light border' : 'bg-success text-white' }}" style="max-width: 80%;">
                                    <div class="small text-muted mb-1">{{ $mensaje->emisor === 'bot' ? '🤖 WallyBot' : '👤 Usuario' }}</div>
                                    <div class="text-break">{{ $mensaje->contenido }}</div>
                                    @if ($mensaje->intencion)
                                        <div class="small mt-1"><span class="badge bg-info text-dark">{{ $mensaje->intencion }}</span></div>
                                    @endif
                                    @if ($mensaje->emisor === 'usuario')
                                        @if ($mensaje->ejecucion)
                                            @php($traza = $mensaje->ejecucion)
                                            <details class="mt-2 small {{ $mensaje->emisor === 'bot' ? 'text-muted' : 'text-white' }}">
                                                <summary>Traza técnica · {{ $traza->engine_used }}</summary>
                                                <div class="mt-2 p-2 rounded bg-white text-dark border">
                                                    <div><strong>Motor:</strong> {{ $traza->engine_used }}</div>
                                                    <div><strong>Proveedor/modelo:</strong> {{ $traza->provider ?? '—' }} / {{ $traza->model ?? '—' }}</div>
                                                    <div><strong>Estado externo:</strong> {{ $traza->external_status }}</div>
                                                    <div><strong>Intentado / utilizado / fallback:</strong>
                                                        {{ $traza->external_attempted ? 'sí' : 'no' }} /
                                                        {{ $traza->external_used ? 'sí' : 'no' }} /
                                                        {{ $traza->fallback_local ? 'sí' : 'no' }}
                                                    </div>
                                                    <div><strong>HTTP:</strong> {{ $traza->external_http_status ?? '—' }}</div>
                                                    <div><strong>Latencia:</strong> {{ $traza->external_latency_ms !== null ? $traza->external_latency_ms.' ms' : '—' }}</div>
                                                    <div><strong>Intención:</strong> {{ $traza->detected_intent ?? '—' }}</div>
                                                    @if ($traza->external_error_code)
                                                        <div><strong>Código diagnóstico:</strong> {{ $traza->external_error_code }}</div>
                                                    @endif
                                                    @if ($traza->external_error_message)
                                                        <div><strong>Error seguro:</strong> {{ $traza->external_error_message }}</div>
                                                    @endif
                                                    @if ($traza->structured_parameters)
                                                        <div class="mt-1"><strong>Parámetros estructurados:</strong></div>
                                                        <pre class="small mb-0 text-wrap">{{ json_encode($traza->structured_parameters, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre>
                                                    @endif
                                                    <div class="text-muted mt-1">{{ $traza->created_at->translatedFormat('d/m/Y H:i:s') }}</div>
                                                </div>
                                            </details>
                                        @else
                                            <div class="small mt-1 opacity-75">Sin traza (mensaje anterior a Observabilidad Fase 1).</div>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
