@php
    $notificacionesRecientes = Auth::user()->unreadNotifications()->latest()->limit(4)->get();
    $totalNoLeidas = Auth::user()->unreadNotifications()->count();
@endphp

<div class="dd-wrap">
    <button type="button" class="icon-btn" data-dropdown aria-label="Notificaciones">
        <svg viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
        @if ($totalNoLeidas > 0)
            <span class="count danger">{{ $totalNoLeidas > 99 ? '99+' : $totalNoLeidas }}</span>
        @endif
    </button>

    <div class="dd-menu dd-notifications" role="menu">
        <div class="dd-head">
            <i class="bi bi-bell"></i>
            <span>Notificaciones</span>
            @if ($totalNoLeidas > 0)
                <span class="ms-auto text-muted">{{ $totalNoLeidas }} sin leer</span>
            @endif
        </div>
        <div class="dd-list">
            @forelse ($notificacionesRecientes as $notificacion)
                <a class="dd-item" href="{{ route('notificaciones.index') }}">
                    <span class="dd-avatar a1"><i class="bi bi-calendar-check"></i></span>
                    <span class="dd-body">
                        <span class="dd-row-head">
                            <strong>{{ $notificacion->data['titulo'] ?? 'Reserva' }}</strong>
                            <span class="dd-time">{{ $notificacion->created_at->diffForHumans(short: true) }}</span>
                        </span>
                        <span class="dd-preview">{{ $notificacion->data['mensaje'] ?? '' }}</span>
                    </span>
                </a>
            @empty
                <div class="notification-empty">No tienes notificaciones nuevas.</div>
            @endforelse
        </div>
        <a class="dd-footer" href="{{ route('notificaciones.index') }}">Ver todas las notificaciones</a>
    </div>
</div>
