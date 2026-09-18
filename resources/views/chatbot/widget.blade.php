{{-- Widget de chat flotante del chatbot --}}
<div id="chatbot-widget" class="chatbot-widget"
     data-chatbot-name="{{ config('chatbot.nombre', 'WallyBot') }}"
     data-authenticated="{{ auth()->check() ? 'true' : 'false' }}"
     @guest
         data-login-url="{{ route('login') }}"
         data-register-url="{{ route('register') }}"
     @endguest>
    <button type="button" id="chatbot-toggle" class="chatbot-toggle" aria-label="Abrir chat de ayuda">
        <span id="chatbot-icono" class="chatbot-icono">💬</span>
        <span id="chatbot-cerrar" class="chatbot-icono d-none">✕</span>
    </button>

    <div id="chatbot-ventana" class="chatbot-ventana d-none">
        <div class="chatbot-header" data-chatbot-drag-handle
             title="Arrastra para mover la ventana">
            <div class="chatbot-identidad">
                <strong>{{ config('chatbot.nombre', 'WallyBot') }}</strong>
                <span class="d-block small">Asistente virtual · en línea</span>
            </div>
            <div class="chatbot-header-acciones">
                @if(auth()->check() && auth()->user()->rol === \App\Models\User::ROL_CLIENTE && auth()->user()->estaActivo())
                    <button type="button" id="chatbot-llamada" class="chatbot-llamada"
                            aria-label="Iniciar llamada de voz" aria-pressed="false"
                            title="Iniciar llamada de voz con WallyBot">
                        <i class="bi bi-telephone-fill" aria-hidden="true"></i>
                    </button>
                @endif
                <button type="button" class="btn-close btn-close-white" id="chatbot-cerrar-ventana" aria-label="Cerrar chat"></button>
            </div>
        </div>

        <div id="chatbot-voz-estado" class="chatbot-voz-estado d-none" role="status" aria-live="polite">
            <span class="chatbot-voz-pulso" aria-hidden="true"></span>
            <span id="chatbot-voz-estado-texto">Llamada inactiva</span>
        </div>

        <div id="chatbot-mensajes" class="chatbot-mensajes">
            <div class="chatbot-mensaje bot">
                ¡Hola! 👋 Soy {{ config('chatbot.nombre', 'WallyBot') }}.
                Puedo ayudarte con horarios, precios, disponibilidad y reservas.
                Escribe <em>"ayuda"</em> para ver mis opciones.
            </div>
        </div>

        <form id="chatbot-form" class="chatbot-form" action="{{ route('chatbot.mensaje') }}" method="POST">
            @csrf
            <input type="text" id="chatbot-input" class="form-control form-control-sm"
                   placeholder="Escribe tu mensaje…" maxlength="500" autocomplete="off">
            <button type="submit" class="btn btn-success btn-sm ms-2">Enviar</button>
        </form>
    </div>
</div>
