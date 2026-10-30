import { CHATBOT_NETWORK_ERROR, interpretarRespuestaChatbot } from './chatbot-http';

/**
 * Widget de chat y llamada de voz de WallyBot.
 * El navegador convierte voz a texto y lee las respuestas; Laravel recibe
 * únicamente el mismo campo `mensaje` que usa el formulario escrito.
 */
document.addEventListener('DOMContentLoaded', function () {
    const widget = document.getElementById('chatbot-widget');
    if (!widget) return;

    const boton = document.getElementById('chatbot-toggle');
    const esInvitado = widget.dataset.authenticated !== 'true';
    const loginUrl = widget.dataset.loginUrl;
    const registerUrl = widget.dataset.registerUrl;
    const ventana = document.getElementById('chatbot-ventana');
    const mensajes = document.getElementById('chatbot-mensajes');
    const formulario = document.getElementById('chatbot-form');
    const entrada = document.getElementById('chatbot-input');
    const icono = document.getElementById('chatbot-icono');
    const iconoCerrar = document.getElementById('chatbot-cerrar');
    const botonLlamada = document.getElementById('chatbot-llamada');
    const estadoVoz = document.getElementById('chatbot-voz-estado');
    const estadoVozTexto = document.getElementById('chatbot-voz-estado-texto');
    const nombreBot = widget.dataset.chatbotName || 'WallyBot';

    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    const vozCompatible = Boolean(
        botonLlamada
        && SpeechRecognition
        && window.speechSynthesis
        && window.SpeechSynthesisUtterance
        && window.isSecureContext
    );

    let reconocimiento = null;
    let llamadaActiva = false;
    let procesando = false;
    let reinicioEscucha = null;
    let avisoIncompatibilidadMostrado = false;
    let voces = [];

    function actualizarEstadoVisual(abierto) {
        document.body.classList.toggle('has-chatbot-open', abierto);
        boton.setAttribute('aria-expanded', String(abierto));
        document.querySelectorAll('[data-chatbot-open]').forEach(function (disparador) {
            disparador.setAttribute('aria-expanded', String(abierto));
        });
    }

    function abrir() {
        ventana.classList.remove('d-none');
        icono.classList.add('d-none');
        iconoCerrar.classList.remove('d-none');
        actualizarEstadoVisual(true);
        entrada.focus();
    }

    function cerrar() {
        finalizarLlamada();
        ventana.classList.add('d-none');
        icono.classList.remove('d-none');
        iconoCerrar.classList.add('d-none');
        actualizarEstadoVisual(false);
    }

    document.querySelectorAll('[data-chatbot-open]').forEach(function (disparador) {
        disparador.addEventListener('click', function (event) {
            event.preventDefault();
            abrir();
        });
    });

    actualizarEstadoVisual(!ventana.classList.contains('d-none'));

    boton.addEventListener('click', () => {
        ventana.classList.contains('d-none') ? abrir() : cerrar();
    });
    document.getElementById('chatbot-cerrar-ventana').addEventListener('click', cerrar);

    const asaArrastre = ventana.querySelector('[data-chatbot-drag-handle]');
    let ventanaMovida = false;
    let arrastrandoVentana = false;
    let desplazamientoX = 0;
    let desplazamientoY = 0;

    function posicionDentroDePantalla(left, top) {
        const margen = 8;
        const anchoMaximo = Math.max(margen, window.innerWidth - ventana.offsetWidth - margen);
        const altoMaximo = Math.max(margen, window.innerHeight - ventana.offsetHeight - margen);

        return {
            left: Math.min(Math.max(left, margen), anchoMaximo),
            top: Math.min(Math.max(top, margen), altoMaximo)
        };
    }

    function moverVentana(left, top) {
        const posicion = posicionDentroDePantalla(left, top);
        ventana.style.left = `${posicion.left}px`;
        ventana.style.top = `${posicion.top}px`;
        ventana.style.right = 'auto';
        ventana.style.bottom = 'auto';
    }

    if (asaArrastre) {
        asaArrastre.addEventListener('pointerdown', function (event) {
            if (event.button !== 0 || event.target.closest('button, a, input')) return;

            const rect = ventana.getBoundingClientRect();
            desplazamientoX = event.clientX - rect.left;
            desplazamientoY = event.clientY - rect.top;
            arrastrandoVentana = true;
            ventanaMovida = true;
            asaArrastre.setPointerCapture(event.pointerId);
            asaArrastre.classList.add('is-dragging');
            document.body.classList.add('is-dragging-chatbot');
            moverVentana(rect.left, rect.top);
            event.preventDefault();
        });

        asaArrastre.addEventListener('pointermove', function (event) {
            if (!arrastrandoVentana) return;
            moverVentana(event.clientX - desplazamientoX, event.clientY - desplazamientoY);
        });

        const terminarArrastre = function (event) {
            if (!arrastrandoVentana) return;
            arrastrandoVentana = false;
            asaArrastre.classList.remove('is-dragging');
            document.body.classList.remove('is-dragging-chatbot');

            if (asaArrastre.hasPointerCapture(event.pointerId)) {
                asaArrastre.releasePointerCapture(event.pointerId);
            }
        };

        asaArrastre.addEventListener('pointerup', terminarArrastre);
        asaArrastre.addEventListener('pointercancel', terminarArrastre);
    }

    window.addEventListener('resize', function () {
        if (!ventanaMovida || ventana.classList.contains('d-none')) return;
        const rect = ventana.getBoundingClientRect();
        moverVentana(rect.left, rect.top);
    });

    if ('ResizeObserver' in window) {
        let ajustePendiente = null;
        const observadorTamano = new ResizeObserver(function () {
            if (!ventanaMovida || ventana.classList.contains('d-none') || ajustePendiente) return;

            ajustePendiente = window.requestAnimationFrame(function () {
                const rect = ventana.getBoundingClientRect();
                moverVentana(rect.left, rect.top);
                ajustePendiente = null;
            });
        });
        observadorTamano.observe(ventana);
    }

    function agregarMensaje(texto, emisor) {
        const div = document.createElement('div');
        div.className = 'chatbot-mensaje ' + emisor;
        div.textContent = texto;
        mensajes.appendChild(div);
        mensajes.scrollTop = mensajes.scrollHeight;
    }

    function agregarIndicadorCarga() {
        const indicador = document.createElement('div');
        indicador.className = 'chatbot-mensaje bot chatbot-cargando';
        const imagen = document.createElement('img');
        imagen.src = '/img/iconos/cargando.gif';
        imagen.alt = '';
        const texto = document.createElement('span');
        texto.textContent = 'Consultando disponibilidad en tiempo real…';
        indicador.append(imagen, texto);
        mensajes.appendChild(indicador);
        mensajes.scrollTop = mensajes.scrollHeight;

        return indicador;
    }

    function agregarComponentes(componentes) {
        componentes.forEach(function (componente) {
            if (componente.tipo === 'opciones' && Array.isArray(componente.items)) {
                const lista = document.createElement('div');
                lista.className = `chatbot-opciones chatbot-opciones--${componente.entidad || 'general'}`;

                componente.items.forEach(function (item) {
                    const botonOpcion = document.createElement('button');
                    botonOpcion.type = 'button';
                    botonOpcion.className = 'chatbot-opcion';

                    if (item.imagen) {
                        const imagen = document.createElement('img');
                        imagen.src = item.imagen;
                        imagen.alt = item.titulo || 'Opción disponible';
                        imagen.loading = 'lazy';
                        botonOpcion.appendChild(imagen);
                    }

                    const cuerpo = document.createElement('span');
                    cuerpo.className = 'chatbot-opcion-cuerpo';
                    const titulo = document.createElement('strong');
                    titulo.textContent = item.titulo || 'Opción';
                    const descripcion = document.createElement('small');
                    descripcion.textContent = item.descripcion || '';
                    const estado = document.createElement('span');
                    estado.className = 'chatbot-opcion-estado';
                    estado.textContent = item.estado || 'Disponible';
                    cuerpo.append(titulo, descripcion, estado);
                    botonOpcion.appendChild(cuerpo);
                    botonOpcion.addEventListener('click', () => enviarMensaje(item.mensaje, false));
                    lista.appendChild(botonOpcion);
                });

                mensajes.appendChild(lista);
            }

            if (componente.tipo === 'acciones' && Array.isArray(componente.acciones)) {
                const acciones = document.createElement('div');
                acciones.className = 'chatbot-reserva-acciones';
                componente.acciones.forEach(function (accion) {
                    const botonAccion = document.createElement('button');
                    botonAccion.type = 'button';
                    botonAccion.className = `chatbot-reserva-accion is-${accion.estilo || 'secondary'}`;
                    botonAccion.textContent = accion.etiqueta;
                    botonAccion.addEventListener('click', () => enviarMensaje(accion.mensaje, false));
                    acciones.appendChild(botonAccion);
                });
                mensajes.appendChild(acciones);
            }

            if (componente.tipo === 'enlace' && componente.url) {
                const enlace = document.createElement('a');
                enlace.className = 'chatbot-reserva-enlace';
                enlace.href = componente.url;
                enlace.textContent = componente.etiqueta || 'Ver detalle';
                mensajes.appendChild(enlace);
            }
        });

        mensajes.scrollTop = mensajes.scrollHeight;
    }

    function agregarMensajeAcceso() {
        const contenedor = document.createElement('div');
        contenedor.className = 'chatbot-mensaje bot chatbot-mensaje-acceso';

        const texto = document.createElement('p');
        texto.textContent = 'Para consultar más información, reservas, horarios u otras opciones necesitas tener una cuenta e iniciar sesión.';
        contenedor.appendChild(texto);

        const acciones = document.createElement('div');
        acciones.className = 'chatbot-acceso-acciones';

        const iniciarSesion = document.createElement('a');
        iniciarSesion.className = 'chatbot-acceso-link chatbot-acceso-link--login';
        iniciarSesion.href = loginUrl;
        iniciarSesion.textContent = 'Ya tengo cuenta';

        const registrarse = document.createElement('a');
        registrarse.className = 'chatbot-acceso-link chatbot-acceso-link--register';
        registrarse.href = registerUrl;
        registrarse.textContent = 'Aún no tengo cuenta';

        acciones.append(iniciarSesion, registrarse);
        contenedor.appendChild(acciones);
        mensajes.appendChild(contenedor);
        mensajes.scrollTop = mensajes.scrollHeight;
    }

    function actualizarEstadoVoz(estado, detalle = '') {
        if (!estadoVoz || !estadoVozTexto || !botonLlamada) return;

        estadoVoz.classList.remove('is-listening', 'is-processing', 'is-speaking');
        const etiquetas = {
            inactiva: 'Llamada inactiva',
            escuchando: 'Escuchando…',
            procesando: 'Consultando reservas…',
            hablando: `${nombreBot} está respondiendo…`
        };

        if (estado === 'inactiva') {
            estadoVoz.classList.add('d-none');
        } else {
            estadoVoz.classList.remove('d-none');
            estadoVoz.classList.add({
                escuchando: 'is-listening',
                procesando: 'is-processing',
                hablando: 'is-speaking'
            }[estado]);
        }

        estadoVozTexto.textContent = detalle || etiquetas[estado];
        botonLlamada.classList.toggle('is-active', llamadaActiva);
        botonLlamada.setAttribute('aria-pressed', llamadaActiva ? 'true' : 'false');
        botonLlamada.setAttribute('aria-label', llamadaActiva ? 'Terminar llamada de voz' : 'Iniciar llamada de voz');
        botonLlamada.title = llamadaActiva ? 'Terminar llamada de voz' : 'Iniciar llamada de voz con WallyBot';

        const iconoTelefono = botonLlamada.querySelector('i');
        if (iconoTelefono) {
            iconoTelefono.className = llamadaActiva
                ? 'bi bi-telephone-x-fill'
                : 'bi bi-telephone-fill';
        }
    }

    function cargarVoces() {
        voces = window.speechSynthesis ? window.speechSynthesis.getVoices() : [];
    }

    function vozEnEspanol() {
        return voces.find((voz) => voz.lang.toLowerCase() === 'es-bo')
            || voces.find((voz) => voz.lang.toLowerCase() === 'es-419')
            || voces.find((voz) => voz.lang.toLowerCase().startsWith('es'))
            || null;
    }

    function textoParaVoz(texto) {
        return texto
            .replace(/https?:\/\/\S+/gi, ' enlace disponible en el chat ')
            .replace(/[\*_`#>•]/g, ' ')
            .replace(/[\u{1F300}-\u{1FAFF}\u{2600}-\u{27BF}]/gu, '')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function detenerReconocimiento() {
        clearTimeout(reinicioEscucha);
        if (!reconocimiento) return;

        try {
            reconocimiento.abort();
        } catch (error) {
            // El reconocimiento ya estaba detenido.
        }
    }

    function iniciarEscucha() {
        if (!llamadaActiva || procesando) return;

        window.speechSynthesis?.cancel();
        actualizarEstadoVoz('escuchando');

        if (!reconocimiento) crearReconocimiento();

        try {
            reconocimiento.start();
        } catch (error) {
            reinicioEscucha = setTimeout(function () {
                if (llamadaActiva && !procesando) iniciarEscucha();
            }, 500);
        }
    }

    function hablar(texto) {
        if (!llamadaActiva || !window.speechSynthesis) return;

        detenerReconocimiento();
        window.speechSynthesis.cancel();
        actualizarEstadoVoz('hablando');

        const locucion = new SpeechSynthesisUtterance(textoParaVoz(texto));
        const voz = vozEnEspanol();
        locucion.lang = voz?.lang || 'es-BO';
        locucion.voice = voz;
        locucion.rate = 1;
        locucion.pitch = 1;
        locucion.onend = function () {
            if (llamadaActiva) iniciarEscucha();
        };
        locucion.onerror = function () {
            if (llamadaActiva) iniciarEscucha();
        };

        window.speechSynthesis.speak(locucion);
    }

    function finalizarLlamada() {
        if (!llamadaActiva && !reconocimiento) return;

        llamadaActiva = false;
        detenerReconocimiento();
        reconocimiento = null;
        window.speechSynthesis?.cancel();
        actualizarEstadoVoz('inactiva');
    }

    function manejarErrorVoz(evento) {
        if (!llamadaActiva || evento.error === 'aborted') return;

        const erroresFatales = {
            'not-allowed': 'No se concedió permiso para usar el micrófono. Puedes continuar escribiendo en el chat.',
            'service-not-allowed': 'El navegador bloqueó el servicio de voz. Puedes continuar escribiendo en el chat.',
            'audio-capture': 'No se encontró un micrófono disponible. Puedes continuar escribiendo en el chat.',
            'network': 'El reconocimiento de voz no está disponible en este momento. Puedes continuar escribiendo.'
        };

        if (erroresFatales[evento.error]) {
            agregarMensaje(erroresFatales[evento.error], 'bot');
            finalizarLlamada();
            return;
        }

        if (evento.error !== 'no-speech') {
            actualizarEstadoVoz('escuchando', 'No entendí. Intentando escuchar nuevamente…');
        }
    }

    function crearReconocimiento() {
        reconocimiento = new SpeechRecognition();
        reconocimiento.lang = 'es-BO';
        reconocimiento.continuous = false;
        reconocimiento.interimResults = true;
        reconocimiento.maxAlternatives = 1;
        reconocimiento.onstart = function () {
            if (llamadaActiva) actualizarEstadoVoz('escuchando');
        };
        reconocimiento.onresult = function (evento) {
            let textoFinal = '';
            let textoParcial = '';

            for (let i = evento.resultIndex; i < evento.results.length; i += 1) {
                const fragmento = evento.results[i][0].transcript;
                if (evento.results[i].isFinal) textoFinal += fragmento;
                else textoParcial += fragmento;
            }

            if (textoParcial.trim()) {
                actualizarEstadoVoz('escuchando', `Escuchando: ${textoParcial.trim()}`);
            }

            if (textoFinal.trim()) {
                actualizarEstadoVoz('procesando');
                enviarMensaje(textoFinal.trim(), true);
            }
        };
        reconocimiento.onerror = manejarErrorVoz;
        reconocimiento.onend = function () {
            if (llamadaActiva && !procesando && estadoVoz?.classList.contains('is-listening')) {
                reinicioEscucha = setTimeout(iniciarEscucha, 350);
            }
        };
    }

    function iniciarLlamada() {
        abrir();

        if (!vozCompatible) {
            if (!avisoIncompatibilidadMostrado) {
                agregarMensaje(
                    'La llamada de voz necesita Chrome o Edge, permiso de micrófono y una conexión HTTPS o localhost. El chat escrito sigue disponible.',
                    'bot'
                );
                avisoIncompatibilidadMostrado = true;
            }
            return;
        }

        llamadaActiva = true;
        cargarVoces();
        actualizarEstadoVoz('hablando');
        hablar(`Hola, soy ${nombreBot}. ¿En qué puedo ayudarte?`);
    }

    async function enviarMensaje(texto, desdeVoz = false) {
        if (!texto || procesando) return;

        procesando = true;
        agregarMensaje(texto, 'usuario');
        entrada.value = '';
        entrada.disabled = true;

        if (esInvitado) {
            agregarMensajeAcceso();
            procesando = false;
            entrada.disabled = false;
            entrada.focus();
            return;
        }

        if (llamadaActiva) {
            detenerReconocimiento();
            window.speechSynthesis?.cancel();
            actualizarEstadoVoz('procesando');
        }

        let respuestaParaVoz = '';
        let componentes = [];
        const indicadorCarga = agregarIndicadorCarga();

        try {
            const respuesta = await fetch(formulario.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': formulario.querySelector('input[name="_token"]').value
                },
                body: JSON.stringify({ mensaje: texto })
            });

            const resultado = await interpretarRespuestaChatbot(respuesta);
            respuestaParaVoz = resultado.respuesta;
            componentes = resultado.componentes || [];
        } catch (error) {
            respuestaParaVoz = CHATBOT_NETWORK_ERROR;
        } finally {
            indicadorCarga.remove();
            agregarMensaje(respuestaParaVoz, 'bot');
            agregarComponentes(componentes);
            procesando = false;
            entrada.disabled = false;
            entrada.focus();
        }

        if (llamadaActiva) {
            hablar(respuestaParaVoz);
        } else if (desdeVoz) {
            actualizarEstadoVoz('inactiva');
        }
    }

    formulario.addEventListener('submit', function (evento) {
        evento.preventDefault();
        const texto = entrada.value.trim();
        if (!texto) return;
        enviarMensaje(texto, false);
    });

    if (botonLlamada) {
        cargarVoces();
        window.speechSynthesis?.addEventListener('voiceschanged', cargarVoces);
        if (!vozCompatible) {
            botonLlamada.classList.add('is-unavailable');
            botonLlamada.title = 'Voz no disponible en este navegador o conexión';
        }

        botonLlamada.addEventListener('click', function () {
            llamadaActiva ? finalizarLlamada() : iniciarLlamada();
        });
    }

    document.addEventListener('visibilitychange', function () {
        if (document.hidden) finalizarLlamada();
    });
    window.addEventListener('pagehide', finalizarLlamada);
});
