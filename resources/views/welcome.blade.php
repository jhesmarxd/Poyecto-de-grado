@extends('layouts.public')

@section('title', 'Reservas y atención para canchas de Wally')

@section('public-header')
<header class="welcome-header">
    <div class="welcome-header__inner">
        <a class="welcome-brand" href="{{ route('inicio') }}" aria-label="Wally Reservas, inicio">
            <span class="welcome-brand__mark" aria-hidden="true">
                <svg viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="40" cy="40" r="37" fill="#005547"/>
                    <path d="M23 57c2-20 11-34 31-43-1 18-9 33-31 43Z" fill="none" stroke="#9af4bd" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M22 62c9-13 20-21 34-30" fill="none" stroke="#9af4bd" stroke-width="3" stroke-linecap="round"/>
                    <circle cx="57" cy="58" r="5" fill="#e9fff2"/>
                </svg>
            </span>
            <span>
                <span class="welcome-brand__name">Wally Reservas</span>
                <span class="welcome-brand__tagline">TU PASIÓN, SIEMPRE EN JUEGO</span>
            </span>
        </a>

        <button class="welcome-header__toggle" type="button" data-bs-toggle="collapse"
                data-bs-target="#welcomeLandingNav" aria-controls="welcomeLandingNav"
                aria-expanded="false" aria-label="Abrir menú de navegación">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M4 7h16M4 12h16M4 17h16" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </button>

        <div class="welcome-header__collapse collapse" id="welcomeLandingNav">
            <nav class="welcome-nav" aria-label="Navegación principal">
                <a class="welcome-nav__link is-active" href="#inicio" aria-current="page">Inicio</a>
                <a class="welcome-nav__link" href="{{ route('canchas.index') }}">Canchas</a>
                <a class="welcome-nav__link" href="#servicios">Servicios</a>
                <button class="welcome-nav__link" type="button" data-chatbot-open>Contacto</button>
            </nav>

            <div class="welcome-header__actions">
                @guest
                    @if (Route::has('login'))
                        <a class="welcome-auth-btn" href="{{ route('login') }}">Iniciar sesión</a>
                    @endif
                    @if (Route::has('register'))
                        <a class="welcome-auth-btn welcome-auth-btn--primary" href="{{ route('register') }}">Registrarse</a>
                    @endif
                @else
                    <a class="welcome-auth-btn welcome-auth-btn--primary"
                       href="{{ Auth::user()->esAdministrador() ? route('admin.dashboard') : route('reservas.mis') }}">
                        Mi panel
                    </a>
                    <a class="welcome-auth-btn" href="{{ route('logout') }}"
                       onclick="event.preventDefault(); document.getElementById('logout-form-welcome').submit();">
                        Cerrar sesión
                    </a>
                    <form id="logout-form-welcome" action="{{ route('logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                @endguest
            </div>
        </div>
    </div>
</header>
@endsection

@section('content')
<div class="welcome-page">
    <section class="landing-hero" id="inicio" aria-labelledby="landing-title">
        <img class="landing-hero__image"
             src="{{ asset('img/wally/welcome-cancha.png') }}"
             alt="Cancha de Wally con paredes verdes y cerramiento de vidrio"
             width="1672" height="941" fetchpriority="high">

        <div class="landing-hero__content">
            <div class="landing-hero__copy">
                <p class="landing-hero__eyebrow">Deporte&nbsp;&nbsp;·&nbsp;&nbsp;Amigos&nbsp;&nbsp;·&nbsp;&nbsp;Grandes momentos</p>
                <h1 class="landing-hero__title" id="landing-title">
                    Reserva tu cancha
                    <span>de forma fácil<br>y rápida</span>
                </h1>
                <p class="landing-hero__lead">
                    Consulta disponibilidad, elige tu horario y asegura tu espacio en segundos.
                    En Wally Reservas hacemos que jugar sea más simple.
                </p>
                <div class="landing-hero__actions">
                    <a class="landing-primary-btn" href="{{ route('disponibilidad.index') }}">
                        <span>Reservar ahora</span>
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h14M13 6l6 6-6 6"/>
                        </svg>
                    </a>
                    <a class="landing-secondary-btn" href="{{ route('canchas.index') }}">
                        <span class="landing-secondary-btn__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="m9 7 8 5-8 5V7Z"/></svg>
                        </span>
                        <span>Conoce nuestras canchas</span>
                    </a>
                </div>
                <p class="landing-hero__signature">El deporte<br>nos conecta</p>
            </div>
        </div>

        <p class="landing-hero__side-note">Más<br>que canchas,<br>es tu lugar</p>

        <div class="landing-features" id="servicios">
            <div class="landing-features__grid">
                <article class="landing-feature-card">
                    <span class="landing-feature-card__icon" aria-hidden="true">
                        <svg viewBox="0 0 36 36">
                            <rect x="3.5" y="5.5" width="29" height="25" rx="3"/>
                            <path d="M18 6v24M3.5 18h29M10 13H7v10h3M26 13h3v10h-3"/>
                        </svg>
                    </span>
                    <div>
                        <strong class="landing-feature-card__value">{{ $metricas['canchas'] }} {{ Str::plural('Cancha', $metricas['canchas']) }} disponibles</strong>
                        <p class="landing-feature-card__description">Instalaciones de primera</p>
                    </div>
                </article>

                <article class="landing-feature-card">
                    <span class="landing-feature-card__icon" aria-hidden="true">
                        <svg viewBox="0 0 36 36">
                            <rect x="4" y="7" width="28" height="25" rx="4"/>
                            <path d="M11 3.5v7M25 3.5v7M4 14h28M11 20h.01M18 20h.01M25 20h.01M11 26h.01M18 26h.01"/>
                        </svg>
                    </span>
                    <div>
                        <strong class="landing-feature-card__value">Horarios flexibles</strong>
                        <p class="landing-feature-card__description">Reserva cuando quieras</p>
                    </div>
                </article>

                <article class="landing-feature-card">
                    <span class="landing-feature-card__icon" aria-hidden="true">
                        <svg viewBox="0 0 36 36">
                            <path d="M21 3 7 21h11l-3 12 14-18H18l3-12Z"/>
                        </svg>
                    </span>
                    <div>
                        <strong class="landing-feature-card__value">Reservas rápidas</strong>
                        <p class="landing-feature-card__description">En solo unos clics</p>
                    </div>
                </article>

                <article class="landing-feature-card">
                    <span class="landing-feature-card__icon" aria-hidden="true">
                        <svg viewBox="0 0 36 36">
                            <circle cx="18" cy="11" r="5"/>
                            <circle cx="8" cy="15" r="3.5"/>
                            <circle cx="28" cy="15" r="3.5"/>
                            <path d="M9 30v-3c0-5 4-8 9-8s9 3 9 8v3M3 29v-2c0-4 2-6 6-6M33 29v-2c0-4-2-6-6-6"/>
                        </svg>
                    </span>
                    <div>
                        <strong class="landing-feature-card__value">Deporte para todos</strong>
                        <p class="landing-feature-card__description">Amigos, familias y equipos</p>
                    </div>
                </article>
            </div>
        </div>
    </section>

{{-- Canchas destacadas --}}
<section class="welcome-courts" aria-labelledby="welcome-courts-title">
    <div class="container">
        <header class="welcome-courts__header">
            <span class="welcome-courts__eyebrow">Conoce nuestros espacios</span>
            <span class="visually-hidden">Canchas destacadas</span>
            <h2 class="welcome-courts__title" id="welcome-courts-title">
                Encuentra la cancha ideal
                <span>para tu partido</span>
            </h2>
            <p class="welcome-courts__description">
                Consulta las características y la disponibilidad de nuestras canchas y elige el espacio
                que mejor se adapte a tu partido.
            </p>
        </header>

        <div class="row g-4">
            @forelse ($canchasDestacadas as $cancha)
                <div class="col-12 col-md-6 col-xl-4 d-flex">
                    <article class="card-cancha">
                        <div class="card-cancha__media">
                            @if ($cancha->imagenUrl($version))
                                <img
                                    class="card-cancha__image"
                                    src="{{ $cancha->imagenUrl($version) }}"
                                    alt="{{ $cancha->nombre }}"
                                    loading="lazy"
                                >
                            @else
                                <div class="card-cancha__placeholder" aria-label="Cancha sin imagen disponible">
                                    <i class="bi bi-image" aria-hidden="true"></i>
                                </div>
                            @endif

                            @if ($cancha->esCerrada())
                                <span class="card-cancha__badge">
                                    <i class="bi bi-house" aria-hidden="true"></i>
                                    Cubierta
                                </span>
                            @endif
                        </div>

                        <div class="card-cancha__body">
                            <h3 class="card-cancha__title">{{ $cancha->nombre }}</h3>

                            <div class="card-cancha__details">
                                <div class="card-cancha__detail">
                                    <span class="card-cancha__detail-icon" aria-hidden="true">
                                        <i class="bi bi-geo-alt"></i>
                                    </span>
                                    <span>{{ $cancha->ubicacion ?? 'Ubicación disponible al consultar' }}</span>
                                </div>

                            </div>

                            <div class="card-cancha__footer">
                                <div class="card-cancha__price">
                                    <span>Precio por hora</span>
                                    <strong>
                                        Bs. {{ number_format($cancha->precio_por_hora, 2) }}
                                        <small>/ hora</small>
                                    </strong>
                                </div>

                                <a class="card-cancha__action" href="{{ route('canchas.show', $cancha) }}">
                                    Ver disponibilidad
                                    <i class="bi bi-arrow-right" aria-hidden="true"></i>
                                </a>
                            </div>
                        </div>
                    </article>
                </div>
            @empty
                <div class="col-12">
                    <div class="welcome-courts__empty">
                        <span class="welcome-courts__empty-icon" aria-hidden="true">
                            <i class="bi bi-calendar2-check"></i>
                        </span>
                        <h3>Próximamente tendremos canchas disponibles</h3>
                        <p>Estamos preparando nuestros espacios para que puedas reservarlos.</p>
                    </div>
                </div>
            @endforelse
        </div>

        @if ($canchasDestacadas->isNotEmpty())
            <div class="welcome-courts__more">
                <a class="welcome-courts__more-btn" href="{{ route('canchas.index') }}">
                    Ver todas las canchas
                    <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </a>
            </div>
        @endif
    </div>
</section>

<div class="welcome-balls">
    <div class="container">
        @include('balones.partials.cards', [
            'balones' => $balones,
            'version' => $version,
            'vistaCompleta' => true,
            'compacto' => true,
        ])
    </div>
</div>

{{-- CTA final --}}
<div class="cta-wally text-white">
    <div class="container py-5 text-center position-relative">
        <h2 class="display-5 fw-bold mb-3">¿Listo para jugar?</h2>
        <p class="fs-5 mb-4" style="color: rgba(255,255,255,.75)">
            Crea tu cuenta gratis y empieza a reservar tu cancha favorita.
        </p>
        @guest
            <div class="d-flex flex-wrap justify-content-center gap-3">
                <a href="{{ route('register') }}" class="btn btn-acento btn-lg px-4 py-3 fw-bold rounded-pill">Crear cuenta</a>
                <a href="{{ route('login') }}" class="btn btn-outline-light btn-lg px-4 py-3 rounded-pill">Iniciar sesión</a>
            </div>
        @else
            <a href="{{ route('disponibilidad.index') }}" class="btn btn-acento btn-lg px-4 py-3 fw-bold rounded-pill">Reservar ahora</a>
        @endguest
    </div>
</div>
</div>
@endsection
