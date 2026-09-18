@extends('layouts.auth')

@section('title', 'Verificar correo')

@section('content')
    <div class="auth-header">
        <div class="auth-logo">
            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" role="img" aria-label="Correo">
                <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                <path d="m3 7 9 6 9-6"></path>
            </svg>
        </div>
        <h2 class="auth-title">Verifica tu correo electrónico</h2>
        <p class="auth-subtitle">Confirma tu dirección para continuar</p>
    </div>

    <div class="auth-form">
        @if (session('resent'))
            <div class="alert alert-success" role="alert">
                Se envió un nuevo enlace de verificación a tu correo electrónico.
            </div>
        @endif

        <p class="text-muted small mb-4">
            Antes de continuar, revisa tu correo electrónico para ver el enlace de verificación.
        </p>

        <form class="d-inline" method="POST" action="{{ route('verification.resend') }}">
            @csrf
            <button type="submit" class="auth-btn">Reenviar enlace de verificación</button>
        </form>

        <div class="auth-switch">
            ¿No recibiste el correo? <a class="auth-link" href="{{ route('verification.resend') }}"
                onclick="event.preventDefault(); this.closest('form').submit();">Solicitar otro enlace</a>
        </div>
    </div>
@endsection