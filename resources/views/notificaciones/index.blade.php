@extends('layouts.app')

@section('title', 'Notificaciones')

@section('content')
    <div class="hero">
        <div class="hero-text">
            <span class="eyebrow">Mi actividad</span>
            <h1 class="hero-title">Mis <span class="accent">notificaciones</span></h1>
            <p class="hero-sub">Confirmaciones, recordatorios y cambios importantes de tus reservas.</p>
        </div>
        @if (Auth::user()->unreadNotifications()->exists())
            <div class="hero-actions">
                <form method="POST" action="{{ route('notificaciones.leer-todas') }}">
                    @csrf
                    <button type="submit" class="btn btn--ghost">Marcar todas como leídas</button>
                </form>
            </div>
        @endif
    </div>

    <div class="notification-list">
        @forelse ($notificaciones as $notificacion)
            <article class="notification-card card {{ $notificacion->read_at ? '' : 'is-unread' }}">
                <div class="notification-card__icon" aria-hidden="true">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <div class="notification-card__body">
                    <div class="notification-card__head">
                        <h2>{{ $notificacion->data['titulo'] ?? 'Actualización de reserva' }}</h2>
                        <time datetime="{{ $notificacion->created_at->toIso8601String() }}">{{ $notificacion->created_at->diffForHumans() }}</time>
                    </div>
                    <p>{{ $notificacion->data['mensaje'] ?? '' }}</p>
                    <div class="notification-card__meta">
                        <span>{{ $notificacion->data['cancha'] ?? 'Cancha' }}</span>
                        <span>{{ isset($notificacion->data['fecha']) ? \Illuminate\Support\Carbon::parse($notificacion->data['fecha'])->format('d/m/Y') : '' }}</span>
                        <span>{{ $notificacion->data['hora'] ?? '' }}</span>
                    </div>
                </div>
                @if (! $notificacion->read_at)
                    <form method="POST" action="{{ route('notificaciones.leer', $notificacion) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn--primary">Ver reserva</button>
                    </form>
                @else
                    <a href="{{ route('reservas.mis') }}" class="btn btn-sm btn--ghost">Ver reserva</a>
                @endif
            </article>
        @empty
            <div class="card text-center p-5">
                <i class="bi bi-bell text-muted fs-2 mb-2"></i>
                <p class="text-muted mb-0">Todavía no tienes notificaciones.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-3">{{ $notificaciones->links() }}</div>
@endsection
