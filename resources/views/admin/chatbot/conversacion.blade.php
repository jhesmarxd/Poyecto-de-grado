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
