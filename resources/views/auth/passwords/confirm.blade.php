@extends('layouts.auth')

@section('title', 'Confirmar contraseña')

@section('content')
    <div class="auth-header">
        <div class="auth-logo">
            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" role="img" aria-label="Candado">
                <rect x="3" y="11" width="18" height="11" rx="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
        </div>
        <h2 class="auth-title">Confirma tu contraseña</h2>
        <p class="auth-subtitle">Verifica tu identidad antes de continuar</p>
    </div>

    <div class="auth-form">
        <form method="POST" action="{{ route('password.confirm') }}">
            @csrf

            <div class="auth-field auth-password">
                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password" placeholder=" ">
                <label for="password">Contraseña</label>

                @error('password')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="auth-actions">
                <button type="submit" class="auth-btn">Confirmar contraseña</button>
            </div>

        </form>
    </div>
@endsection
