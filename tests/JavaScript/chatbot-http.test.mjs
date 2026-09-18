import assert from 'node:assert/strict';
import {
    CHATBOT_NETWORK_ERROR,
    interpretarRespuestaChatbot,
    mensajeErrorChatbot,
} from '../../resources/js/chatbot-http.js';

const expectedByStatus = new Map([
    [419, 'Tu sesión venció. Recarga la página e inténtalo nuevamente.'],
    [429, 'Has enviado demasiados mensajes. Espera un momento antes de volver a intentarlo.'],
    [422, 'El mensaje no es válido. Revísalo e inténtalo nuevamente.'],
    [500, 'El chatbot está temporalmente fuera de servicio. Inténtalo nuevamente en unos segundos.'],
    [503, 'El chatbot está temporalmente fuera de servicio. Inténtalo nuevamente en unos segundos.'],
]);

for (const [status, expected] of expectedByStatus) {
    const response = new Response('{}', {
        status,
        headers: { 'content-type': 'application/json' },
    });

    assert.equal(mensajeErrorChatbot(status), expected);
    assert.deepEqual(await interpretarRespuestaChatbot(response), { ok: false, respuesta: expected });
}

const success = new Response(JSON.stringify({ ok: true, respuesta: 'Respuesta correcta' }), {
    status: 200,
    headers: { 'content-type': 'application/json; charset=UTF-8' },
});
assert.deepEqual(await interpretarRespuestaChatbot(success), {
    ok: true,
    respuesta: 'Respuesta correcta',
    componentes: [],
});

const richSuccess = new Response(JSON.stringify({
    ok: true,
    respuesta: 'Elige una cancha',
    componentes: [{ tipo: 'opciones', entidad: 'cancha', items: [] }],
}), {
    status: 200,
    headers: { 'content-type': 'application/json' },
});
assert.equal((await interpretarRespuestaChatbot(richSuccess)).componentes[0].entidad, 'cancha');

const htmlError = new Response('<html>Error</html>', {
    status: 200,
    headers: { 'content-type': 'text/html' },
});
assert.deepEqual(await interpretarRespuestaChatbot(htmlError), {
    ok: false,
    respuesta: mensajeErrorChatbot(500),
});

assert.equal(CHATBOT_NETWORK_ERROR, 'Lo siento, no pude conectarme. Inténtalo de nuevo en unos segundos.');

console.log('Chatbot HTTP: 419, 422, 429, 500, 503, JSON y fallo de red verificados.');
