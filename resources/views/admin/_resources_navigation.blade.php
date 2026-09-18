<nav class='tabs pills' aria-label='Secciones de recursos'>
    <a class='tab {{ request()->routeIs('admin.canchas.*') ? 'is-active' : '' }}' href='{{ route('admin.canchas.index') }}'>Canchas</a>
    <a class='tab {{ request()->routeIs('admin.horarios.*') ? 'is-active' : '' }}' href='{{ route('admin.horarios.index') }}'>Franjas reservables</a>
    <a class='tab {{ request()->routeIs('admin.balones.*') ? 'is-active' : '' }}' href='{{ route('admin.balones.index') }}'>Balones</a>
</nav>
