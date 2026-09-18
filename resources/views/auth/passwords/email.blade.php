@extends('layouts.auth')

@section('title', 'Restablecer contraseña')

@section('content')
    <div class="auth-header">
        <div class="auth-logo">
            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" role="img" aria-label="Correo">
                <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                <path d="m3 7 9 6 9-6"></path>
            </svg>
        </div>
        <h2 class="auth-title">Restablecer contraseña</h2>
        <p class="auth-subtitle">Te enviaremos un enlace para recuperar tu acceso</p>
    </div>

    <div class="auth-form">
        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <div class="auth-field">
                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus placeholder=" ">
                <label for="email">Correo electrónico</label>

                @error('email')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="auth-actions">
                <button type="submit" class="auth-btn">Enviar enlace de restablecimiento</button>
            </div>

            <div class="auth-switch">
                ¿Recordaste tu contraseña? <a class="auth-link" href="{{ route('login') }}">Inicia sesión aquí</a>
            </div>
        </form>
    </div>
@endsection