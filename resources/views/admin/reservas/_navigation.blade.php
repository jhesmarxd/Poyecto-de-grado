<nav class="tabs pills" aria-label="Reservas y agenda">
    <a class="tab {{ request()->routeIs('admin.reservas.calendario') ? 'is-active' : '' }}"
       href="{{ route('admin.reservas.calendario') }}">Calendario de reservas</a>
    <a class="tab {{ request()->routeIs('admin.reservas.index') ? 'is-active' : '' }}"
       href="{{ route('admin.reservas.index') }}">Gestión de reservas</a>
</nav>
