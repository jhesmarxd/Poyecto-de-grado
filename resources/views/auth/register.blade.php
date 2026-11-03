@extends('layouts.auth')

@section('title', 'Registrarse')
@section('auth-centered', '1')

@section('content')
    <div class="auth-header">
        <div class="auth-logo">
            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" role="img" aria-label="Pelota">
                <circle cx="12" cy="12" r="9.5"></circle>
                <path d="M12 6.5 16.8 9.2 15.4 14.6H8.6L7.2 9.2 12 6.5Z"></path>
                <path d="M12 6.5V2.5"></path>
            </svg>
        </div>
        <h2 class="auth-title">Registrarse</h2>
        <p class="auth-subtitle">Crea tu cuenta y empieza a reservar</p>
    </div>

    <div class="auth-form">
        @if (session('reserva_pendiente'))
            <p class="alert alert-info">Al registrarte volverás a consultar los horarios de {{ session('reserva_pendiente') }} con la fecha que elegiste.</p>
        @endif
        <form method="POST" action="{{ route('register') }}" class="auth-register-form">
            @csrf

            <div class="auth-field" style="animation-delay: 0.1s">
                <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus placeholder=" ">
                <label for="name">Nombre completo</label>

                @error('name')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="auth-field" style="animation-delay: 0.15s">
                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder=" ">
                <label for="email">Correo electrónico</label>

                @error('email')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="auth-field" style="animation-delay: 0.2s">
                <input id="telefono" type="text" class="form-control @error('telefono') is-invalid @enderror" name="telefono" value="{{ old('telefono') }}" autocomplete="tel" placeholder=" ">
                <label for="telefono">Teléfono (opcional)</label>

                @error('telefono')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="auth-field auth-password" style="animation-delay: 0.25s">
                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password" placeholder=" ">
                <label for="password">Contraseña</label>
                <button type="button" class="password-toggle" data-target="password" aria-label="Mostrar contraseña">
                    <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                    <svg class="icon-eye-off" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"></path>
                        <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c6.5 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"></path>
                        <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3.5 7 10 7a9.74 9.74 0 0 0 5.39-1.61"></path>
                        <line x1="2" x2="22" y1="2" y2="22"></line>
                    </svg>
                </button>

                @error('password')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="auth-field auth-password" style="animation-delay: 0.3s">
                <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password" placeholder=" ">
                <label for="password-confirm">Confirmar contraseña</label>
                <button type="button" class="password-toggle" data-target="password-confirm" aria-label="Mostrar contraseña">
                    <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                    <svg class="icon-eye-off" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"></path>
                        <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c6.5 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"></path>
                        <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3.5 7 10 7a9.74 9.74 0 0 0 5.39-1.61"></path>
                        <line x1="2" x2="22" y1="2" y2="22"></line>
                    </svg>
                </button>
            </div>

            <div class="auth-actions" style="animation-delay: 0.4s">
                <button type="submit" class="auth-btn">
                    Registrarse
                </button>
            </div>

            @if (filled(config('services.google.client_id')) && filled(config('services.google.client_secret')))
                <div class="auth-google-last" style="animation-delay: 0.6s">
                    <div class="auth-divider"><span></span></div>
                    <a href="{{ route('auth.google.redirect') }}" class="google-auth-btn">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285F4" d="M21.6 12.23c0-.71-.06-1.4-.18-2.07H12v3.91h5.38a4.6 4.6 0 0 1-2 3.02v2.54h3.24c1.9-1.75 2.98-4.33 2.98-7.4Z"/><path fill="#34A853" d="M12 22c2.7 0 4.97-.9 6.62-2.37l-3.24-2.54c-.9.6-2.05.96-3.38.96-2.61 0-4.82-1.76-5.61-4.13H3.04v2.62A10 10 0 0 0 12 22Z"/><path fill="#FBBC05" d="M6.39 13.92A6 6 0 0 1 6.08 12c0-.67.11-1.32.31-1.92V7.46H3.04A10 10 0 0 0 2 12c0 1.61.38 3.14 1.04 4.54l3.35-2.62Z"/><path fill="#EA4335" d="M12 5.95c1.47 0 2.78.5 3.82 1.49l2.87-2.87A9.63 9.63 0 0 0 12 2a10 10 0 0 0-8.96 5.46l3.35 2.62C7.18 7.71 9.39 5.95 12 5.95Z"/></svg>
                        Continuar con Google
                    </a>
                </div>
            @endif

            <div class="auth-switch" style="animation-delay: 0.5s">
                ¿Ya tienes cuenta? <a class="auth-link" href="{{ route('login') }}">Inicia sesión aquí</a>
            </div>
        </form>
    </div>
@endsection
