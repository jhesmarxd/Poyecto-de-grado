<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Configuración del chatbot
    |--------------------------------------------------------------------------
    |
    | El motor principal del chatbot es local (reglas en español + datos reales
    | de la base de datos), por lo que funciona sin conexión ni claves.
    |
    | De forma opcional se puede configurar un proveedor externo en .env.
    | El proveedor devuelve una sola salida estructurada con intención y
    | entidades; el backend conserva el control de todos los datos reales.
    |
    */

    'nombre' => env('CHATBOT_NOMBRE', 'WallyBot'),

    // Proveedor externo opcional: null, 'openai' o 'gemini'
    'provider' => env('CHATBOT_PROVIDER'),

    // Clave de API del proveedor externo (solo se usa en el servidor)
    'api_key' => env('CHATBOT_API_KEY'),

    // Clave especifica de Gemini. CHATBOT_API_KEY es compatibilidad temporal.
    'gemini_api_key' => env('GEMINI_API_KEY') ?: env('CHATBOT_API_KEY'),

    // Modelo del proveedor externo (vacío = modelo por defecto según proveedor:
    // 'gemini-3.6-flash' para Gemini, 'gpt-4o-mini' para OpenAI)
    'model' => env('CHATBOT_MODEL'),

    // Datos de contacto que el bot puede compartir
    'telefono' => env('CHATBOT_TELEFONO'),
    'direccion' => env('CHATBOT_DIRECCION'),

    'rate_limit' => [
        'guest' => (int) env('CHATBOT_RATE_LIMIT_GUEST', 10),
        'authenticated' => (int) env('CHATBOT_RATE_LIMIT_AUTHENTICATED', 30),
    ],
];