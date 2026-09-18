export const CHATBOT_NETWORK_ERROR = 'Lo siento, no pude conectarme. Inténtalo de nuevo en unos segundos.';

export function mensajeErrorChatbot(status) {
    if (status === 419) {
        return 'Tu sesión venció. Recarga la página e inténtalo nuevamente.';
    }

    if (status === 429) {
        return 'Has enviado demasiados mensajes. Espera un momento antes de volver a intentarlo.';
    }

    if (status === 422) {
        return 'El mensaje no es válido. Revísalo e inténtalo nuevamente.';
    }

    if (status === 500 || status === 503) {
        return 'El chatbot está temporalmente fuera de servicio. Inténtalo nuevamente en unos segundos.';
    }

    return 'Lo siento, hubo un problema al procesar tu mensaje. Inténtalo de nuevo.';
}

export async function interpretarRespuestaChatbot(response) {
    if (!response.ok) {
        return { ok: false, respuesta: mensajeErrorChatbot(response.status) };
    }

    const contentType = response.headers.get('content-type') || '';
    if (!contentType.toLowerCase().includes('application/json')) {
        return { ok: false, respuesta: mensajeErrorChatbot(500) };
    }

    try {
        const datos = await response.json();

        if (datos?.ok === true && typeof datos.respuesta === 'string' && datos.respuesta.trim() !== '') {
            return {
                ok: true,
                respuesta: datos.respuesta,
                componentes: Array.isArray(datos.componentes) ? datos.componentes : []
            };
        }
    } catch {
        // JSON malformado es un error controlado del servidor, no de red.
    }

    return { ok: false, respuesta: mensajeErrorChatbot(500) };
}
