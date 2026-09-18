@extends('layouts.app')

@section('title', 'Nuevo Usuario')

@section('content')
    <div class="hero">
        <div class="hero-text">
            <span class="eyebrow">Usuarios · Crear</span>
            <h1 class="hero-title">Nuevo <span class="accent">usuario</span></h1>
            <p class="hero-sub">Crea una cuenta para un cliente o administrador del sistema.</p>
        </div>
    </div>

    <div class="grid">
        <div class="card col-8">
            <div class="card-head">
                <div class="card-title-wrap">
                    <span class="eyebrow">Datos de la cuenta</span>
                    <h3 class="card-title">Información del usuario</h3>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.usuarios.store') }}">
                @csrf

                <div class="form-grid">
                    <div class="field">
                        <label for="name" class="field-label">Nombre completo</label>
                        <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autofocus>
                        @error('name')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="email" class="field-label">Correo electrónico</label>
                        <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required>
                        @error('email')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="telefono" class="field-label">Teléfono</label>
                        <input id="telefono" type="text" class="form-control @error('telefono') is-invalid @enderror" name="telefono" value="{{ old('telefono') }}">
                        @error('telefono')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="rol" class="field-label">Rol</label>
                        <select id="rol" name="rol" class="form-select @error('rol') is-invalid @enderror" required>
                            <option value="cliente" @selected(old('rol') === 'cliente')>Cliente</option>
                            <option value="admin" @selected(old('rol') === 'admin')>Administrador</option>
                            <option value='recepcionista' @selected(old('rol') === 'recepcionista')>Recepcionista</option>
                        </select>
                        @error('rol')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="password" class="field-label">Contraseña</label>
                        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">
                        @error('password')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="password-confirm" class="field-label">Confirmar contraseña</label>
                        <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">
                    </div>
                </div>

                <div class="form-actions">
                    <a href="{{ route('admin.usuarios.index') }}" class="btn btn--ghost">Cancelar</a>
                    <span class="spacer"></span>
                    <button type="submit" class="btn btn--primary">Guardar usuario</button>
                </div>
            </form>
        </div>
    </div>
@endsection
