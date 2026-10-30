@extends('layouts.app')

@section('title', 'Notificaciones')

@section('content')
    <div class="client-page client-notifications-page">
    <header class="client-page-header client-page-header--with-action">
        <div class="client-page-header__icon" aria-hidden="true"><i class="bi bi-bell"></i></div>
        <div>
            <span class="client-kicker">Tu actividad</span>
            <h1>Mis <span>notificaciones</span></h1>
            <p>Confirmaciones, recordatorios y cambios importantes de tus reservas.</p>
        </div>
        @if (Auth::user()->unreadNotifications()->exists())
            <div class="client-page-header__action">
                <form method="POST" action="{{ route('notificaciones.leer-todas') }}">
                    @csrf
                    <button type="submit" class="btn btn--ghost"><i class="bi bi-check2-all" aria-hidden="true"></i> Marcar todas como leídas</button>
                </form>
            </div>
        @endif
    </header>

    <div class="notification-list">
        @forelse ($notificaciones as $notificacion)
            <article class="notification-card card {{ $notificacion->read_at ? '' : 'is-unread' }}">
                <div class="notification-card__icon" aria-hidden="true">
                    <i class="bi {{ $notificacion->read_at ? 'bi-calendar-check' : 'bi-bell-fill' }}"></i>
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
                    <button type="submit" class="btn btn--primary">Ver reserva</button>
                </form>
            @else
                    <a href="{{ route('reservas.mis') }}" class="btn btn--ghost">Ver reserva</a>
                @endif
            </article>
        @empty
            <div class="card client-empty-state">
                <i class="bi bi-bell" aria-hidden="true"></i>
                <strong>Todavía no tienes notificaciones</strong>
                <span>Aquí aparecerán las novedades importantes de tus reservas.</span>
            </div>
        @endforelse
    </div>

    <div class="mt-3">{{ $notificaciones->links() }}</div>
    </div>
@endsection
