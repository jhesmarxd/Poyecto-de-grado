@php($colores = $colores ?? \App\Models\Ajuste::colores())
<style>
    :root {
        --color-primario: {{ $colores['color_primario'] }};
        --color-primario-oscuro: {{ $colores['color_primario_oscuro'] }};
        --color-primario-rgb: {{ $colores['color_primario_rgb'] }};
        --color-acento: {{ $colores['color_acento'] }};
        --color-acento-oscuro: {{ $colores['color_acento_oscuro'] }};
        --color-acento-rgb: {{ $colores['color_acento_rgb'] }};
        --color-oscuro: {{ $colores['color_oscuro'] }};
        --color-oscuro-rgb: {{ $colores['color_oscuro_rgb'] }};

        /* Aplica el color principal a componentes Bootstrap del sitio */
        --bs-success: var(--color-primario);
        --bs-success-rgb: {{ $colores['color_primario_rgb'] }};
        --bs-dark: var(--color-oscuro);
        --bs-dark-rgb: {{ $colores['color_oscuro_rgb'] }};
    }

    /* Conecta los colores dinámicos con los tokens 2026. Mismo
       selector (especificidad) que los tokens para ganarles al
       venir después en la cascada (este <style> va tras el CSS). */
    :root[data-theme="light"],
    :root[data-theme="dark"] {
        --primary: var(--color-primario);
        --primary-dark: var(--color-primario-oscuro);
        --primary-light: color-mix(in srgb, var(--color-primario) 82%, white);
        --primary-soft: color-mix(in srgb, var(--color-primario) 12%, var(--bg-card));
        --primary-ring: color-mix(in srgb, var(--color-primario) 20%, transparent);

        --accent: var(--color-acento);
        --accent-oscuro: var(--color-acento-oscuro);
        --accent-soft: color-mix(in srgb, var(--color-acento) 14%, var(--bg-card));

        --dark: var(--color-oscuro);

        --success: var(--color-primario);
        --success-soft: var(--primary-soft);
    }

</style>
