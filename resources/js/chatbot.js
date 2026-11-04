import { CHATBOT_NETWORK_ERROR, interpretarRespuestaChatbot } from './chatbot-http';

/** Widget de chat escrito de WallyBot. */
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
    let procesando = false;

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
                    botonOpcion.disabled = Boolean(item.disabled);
                    botonOpcion.classList.toggle('is-unavailable', Boolean(item.disabled));

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
                    botonOpcion.addEventListener('click', () => enviarMensaje(item.mensaje));
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
                    botonAccion.addEventListener('click', () => enviarMensaje(accion.mensaje));
                    acciones.appendChild(botonAccion);
                });
                mensajes.appendChild(acciones);
            }

            if (componente.tipo === 'contacto') {
                const form = document.createElement('form');
                form.className = 'chatbot-contacto';
                const label = document.createElement('label');
                label.textContent = 'Celular para la reserva';
                const input = document.createElement('input');
                input.type = 'tel';
                input.required = true;
                input.inputMode = 'tel';
                input.autocomplete = 'tel';
                input.placeholder = '7XXXXXXX';
                const save = document.createElement('button');
                save.type = 'submit';
                save.textContent = 'Guardar celular';
                form.append(label, input, save);
                form.addEventListener('submit', async event => {
                    event.preventDefault();
                    const phone = input.value.trim();
                    if (!/^(?:\+591)?[67][0-9]{7}$/.test(phone)) {
                        input.setCustomValidity('Ingresa 8 dígitos que comiencen con 6 o 7.');
                        input.reportValidity();
                        return;
                    }
                    input.setCustomValidity('');
                    save.disabled = true;
                    try {
                        const response = await fetch(widget.dataset.phoneUrl, {
                            method: 'POST', credentials: 'same-origin',
                            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': formulario.querySelector('input[name="_token"]').value },
                            body: new URLSearchParams({ telefono: phone }),
                        });
                        if (!response.ok) throw new Error('No se pudo guardar el celular.');
                        form.remove();
                        agregarMensaje('Celular guardado en tu perfil. Revisa el resumen y confirma la reserva.', 'bot');
                        agregarComponentes([{ tipo: 'acciones', acciones: [
                            { etiqueta: 'Sí, confirmar reserva', mensaje: 'sí, confirmar', estilo: 'primary' },
                            { etiqueta: 'No, cancelar', mensaje: 'no', estilo: 'secondary' },
                        ] }]);
                    } catch (error) {
                        agregarMensaje(error.message, 'bot');
                        save.disabled = false;
                    }
                });
                mensajes.appendChild(form);
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

    async function enviarMensaje(texto) {
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

        let respuestaTexto = '';
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
            respuestaTexto = resultado.respuesta;
            componentes = resultado.componentes || [];
        } catch (error) {
            respuestaTexto = CHATBOT_NETWORK_ERROR;
        } finally {
            indicadorCarga.remove();
            agregarMensaje(respuestaTexto, 'bot');
            agregarComponentes(componentes);
            procesando = false;
            entrada.disabled = false;
            entrada.focus();
        }
    }

    formulario.addEventListener('submit', function (evento) {
        evento.preventDefault();
        const texto = entrada.value.trim();
        if (!texto) return;
        enviarMensaje(texto);
    });
});
