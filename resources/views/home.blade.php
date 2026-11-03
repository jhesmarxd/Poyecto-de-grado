@extends('layouts.app')

@section('title', 'Mi panel')

@section('content')
    @php($nombreCorto = \Illuminate\Support\Str::before(Auth::user()->name, ' '))

    <div class="client-page client-home-page">
        <section class="client-home-hero" aria-labelledby="client-home-title">
            <div class="client-home-hero__copy">
                <span class="client-kicker"><i class="bi bi-emoji-smile" aria-hidden="true"></i> Tu espacio de reservas</span>
                <h1 id="client-home-title">¡Hola, <span>{{ $nombreCorto }}</span>!</h1>
                <p>Reserva una cancha, revisa tus turnos o pide ayuda. Todo está organizado para que sea rápido y fácil.</p>
                <div class="client-home-hero__benefits" aria-label="Beneficios del sistema">
                    <span><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Pasos claros</span>
                    <span><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Horarios en tiempo real</span>
                </div>
            </div>
        </section>

        <section class="client-actions" aria-labelledby="client-actions-title">
            <div class="client-section-heading">
                <div>
                    <span class="client-kicker">¿Qué quieres hacer?</span>
                    <h2 id="client-actions-title">Elige una opción</h2>
                </div>
                <p>Los accesos más importantes están siempre a la vista.</p>
            </div>

            <div class="client-actions__grid">
                <x-client-action-card
                    :href="route('disponibilidad.index')"
                    icon="bi-calendar-plus"
                    title="Reservar cancha"
                    description="Consulta la disponibilidad y elige tu horario."
                    tone="lime" />
                <x-client-action-card
                    :href="route('reservas.mis')"
                    icon="bi-calendar2-check"
                    title="Mis reservas"
                    description="Revisa tus turnos y sus estados."
                    tone="sky" />
                <x-client-action-card
                    icon="bi-chat-dots-fill"
                    title="Abrir asistente"
                    description="Recibe ayuda con horarios y reservas."
                    tone="teal"
                    :chatbot="true" />
            </div>
        </section>

        <section class="client-how" aria-labelledby="client-how-title">
            <div class="client-section-heading client-section-heading--compact">
                <div>
                    <span class="client-kicker">Reservar es sencillo</span>
                    <h2 id="client-how-title">Solo necesitas tres pasos</h2>
                </div>
            </div>
            <ol class="client-how__steps">
                <li><span>1</span><div><strong>Elige la cancha</strong><small>Selecciona dónde quieres jugar.</small></div></li>
                <li><span>2</span><div><strong>Busca una fecha</strong><small>Consulta los horarios disponibles.</small></div></li>
                <li><span>3</span><div><strong>Confirma tu reserva</strong><small>Revisa la información y listo.</small></div></li>
            </ol>
        </section>
    </div>
@endsection
