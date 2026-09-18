<nav class='tabs pills' aria-label='Secciones de análisis'>
    <a class='tab {{ request()->routeIs('admin.reportes.*') ? 'is-active' : '' }}' href='{{ route('admin.reportes.index') }}'>Reportes operativos</a>
    <a class='tab {{ request()->routeIs('admin.chatbot.metricas') ? 'is-active' : '' }}' href='{{ route('admin.chatbot.metricas') }}'>Métricas del chatbot</a>
    <a class='tab {{ request()->routeIs('admin.chatbot.conversaciones*') ? 'is-active' : '' }}' href='{{ route('admin.chatbot.conversaciones') }}'>Conversaciones del chatbot</a>
</nav>
