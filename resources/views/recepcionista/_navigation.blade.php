<nav class="tabs pills" aria-label="Agenda operativa">
    <a class="tab {{ request()->routeIs('recepcionista.reservas.hoy') ? 'is-active' : '' }}"
       href="{{ route('recepcionista.reservas.hoy') }}">Agenda operativa</a>
    <a class="tab {{ request()->routeIs('recepcionista.reservas.calendario') ? 'is-active' : '' }}"
       href="{{ route('recepcionista.reservas.calendario') }}">Calendario operativo</a>
    <a class="tab {{ request()->routeIs('recepcionista.reservas.index') ? 'is-active' : '' }}"
       href="{{ route('recepcionista.reservas.index') }}">Historial de reservas</a>
    <a class="tab {{ request()->routeIs('recepcionista.clientes.*') ? 'is-active' : '' }}"
       href="{{ route('recepcionista.clientes.buscar') }}">Clientes y atención</a>
</nav>
