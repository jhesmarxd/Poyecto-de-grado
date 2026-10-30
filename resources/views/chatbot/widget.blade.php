{{-- Widget de chat flotante del chatbot --}}
<div id="chatbot-widget" class="chatbot-widget"
     data-chatbot-name="{{ config('chatbot.nombre', 'WallyBot') }}"
     data-authenticated="{{ auth()->check() ? 'true' : 'false' }}"
     @guest
         data-login-url="{{ route('login') }}"
         data-register-url="{{ route('register') }}"
    @endguest>
    <button type="button" id="chatbot-toggle" class="chatbot-toggle" aria-label="Abrir chat de ayuda">
        <span id="chatbot-icono" class="chatbot-icono"><i class="bi bi-headset" aria-hidden="true"></i></span>
        <span id="chatbot-cerrar" class="chatbot-icono d-none">✕</span>
    </button>

    <div id="chatbot-ventana" class="chatbot-ventana d-none">
        <div class="chatbot-header" data-chatbot-drag-handle
             title="Arrastra para mover la ventana">
            <span class="chatbot-avatar" aria-hidden="true"><i class="bi bi-chat-heart"></i></span>
            <div class="chatbot-identidad">
                <strong>{{ config('chatbot.nombre', 'WallyBot') }}</strong>
                <span class="d-block small"><i class="bi bi-circle-fill" aria-hidden="true"></i> Asistente virtual en línea</span>
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
                <strong>¡Hola! Soy {{ config('chatbot.nombre', 'WallyBot') }}.</strong>
                <span>Puedo ayudarte con horarios, precios, disponibilidad y reservas. Escribe <em>"ayuda"</em> para ver mis opciones.</span>
            </div>
        </div>

        <form id="chatbot-form" class="chatbot-form" action="{{ route('chatbot.mensaje') }}" method="POST">
            @csrf
            <label class="visually-hidden" for="chatbot-input">Escribe tu mensaje</label>
            <input type="text" id="chatbot-input" class="form-control form-control-sm"
                   placeholder="Escribe tu mensaje…" maxlength="500" autocomplete="off">
            <button type="submit" class="btn btn-success btn-sm"><span>Enviar</span><i class="bi bi-send" aria-hidden="true"></i></button>
        </form>
    </div>
</div>
