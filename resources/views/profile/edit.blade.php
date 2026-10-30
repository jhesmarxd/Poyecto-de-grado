@extends('layouts.app')

@section('title', 'Mi Perfil')

@section('content')
    <div class="client-page client-profile-page">
        <header class="client-page-header">
            <div class="client-page-header__icon" aria-hidden="true"><i class="bi bi-person-circle"></i></div>
            <div>
                <span class="client-kicker">Tu cuenta</span>
                <h1>Mi <span>perfil</span></h1>
                <p>Revisa y actualiza tus datos personales de forma segura.</p>
            </div>
        </header>

        <div class="client-profile-grid">
        <div class="card client-profile-card">
            <div class="card-head">
                <div class="card-title-wrap">
                    <span class="client-card-icon" aria-hidden="true"><i class="bi bi-person-vcard"></i></span>
                    <div><span class="client-kicker">Datos personales</span><h2 class="card-title">Información de la cuenta</h2></div>
                </div>
            </div>

            <form method="POST" action="{{ route('perfil.update') }}">
                @csrf
                @method('PUT')

                <div class="form-grid">
                    <div class="field">
                        <label for="name" class="field-label"><i class="bi bi-person" aria-hidden="true"></i> Nombre completo</label>
                        <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', Auth::user()->name) }}" required autofocus>
                        @error('name')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="email" class="field-label"><i class="bi bi-envelope" aria-hidden="true"></i> Correo electrónico</label>
                        <input id="email" type="email" class="form-control" value="{{ Auth::user()->email }}" disabled>
                        <span class="field-help">El correo no se puede cambiar desde el perfil.</span>
                    </div>

                    <div class="field">
                        <label for="telefono" class="field-label"><i class="bi bi-telephone" aria-hidden="true"></i> Teléfono</label>
                        <input id="telefono" type="text" class="form-control @error('telefono') is-invalid @enderror" name="telefono" value="{{ old('telefono', Auth::user()->telefono) }}">
                        @error('telefono')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="field col-12">
                        <div class="section-h"><i class="bi bi-shield-lock" aria-hidden="true"></i> Cambiar contraseña</div>
                    </div>

                    <div class="field">
                        <label for="password" class="field-label">Nueva contraseña</label>
                        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" autocomplete="new-password" placeholder="Déjalo vacío para no cambiarla">
                        @error('password')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="password-confirm" class="field-label">Confirmar nueva contraseña</label>
                        <input id="password-confirm" type="password" class="form-control" name="password_confirmation" autocomplete="new-password">
                    </div>
                </div>

                <div class="form-actions">
                    <span class="spacer"></span>
                    <button type="submit" class="btn btn--primary"><i class="bi bi-check-circle" aria-hidden="true"></i> Guardar cambios</button>
                </div>
            </form>
        </div>

        <div class="card client-google-card">
            <div class="card-head">
                <div class="card-title-wrap">
                    <span class="client-card-icon client-card-icon--google" aria-hidden="true"><i class="bi bi-google"></i></span>
                    <div><span class="client-kicker">Acceso externo</span><h2 class="card-title">Cuenta de Google</h2></div>
                </div>
            </div>

            @error('google')
                <div class="alert alert-danger">{{ $message }}</div>
            @enderror

            @if (Auth::user()->googleAccount)
                <p class="text-muted">Vinculada como <strong>{{ Auth::user()->googleAccount->provider_email }}</strong>.</p>
                <form method="POST" action="{{ route('perfil.google.unlink') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn--ghost">Desvincular Google</button>
                </form>
            @elseif (filled(config('services.google.client_id')) && filled(config('services.google.client_secret')))
                <p class="text-muted">Vincula Google para iniciar sesión sin escribir tu contraseña.</p>
                <a href="{{ route('perfil.google.link') }}" class="btn btn--primary">Vincular Google</a>
            @else
                <p class="text-muted mb-0">La autenticación con Google todavía no está configurada.</p>
            @endif
        </div>
        </div>
    </div>
@endsection
