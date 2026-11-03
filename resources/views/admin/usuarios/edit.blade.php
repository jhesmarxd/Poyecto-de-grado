@extends('layouts.app')

@section('title', 'Editar Usuario')

@section('content')
    <div class="hero">
        <div class="hero-text">
            <span class="eyebrow">Usuarios · Editar</span>
            <h1 class="hero-title">Editar <span class="accent">{{ $usuario->name }}</span></h1>
            <p class="hero-sub">Actualiza los datos y permisos del usuario.</p>
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

            <form method="POST" action="{{ route('admin.usuarios.update', $usuario) }}">
                @csrf
                @method('PUT')

                <div class="form-grid">
                    <div class="field">
                        <label for="name" class="field-label">Nombre completo</label>
                        <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $usuario->name) }}" required autofocus>
                        @error('name')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="email" class="field-label">Correo electrónico</label>
                        <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $usuario->email) }}" required>
                        @error('email')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="telefono" class="field-label">Teléfono</label>
                        <input id="telefono" type="text" class="form-control @error('telefono') is-invalid @enderror" name="telefono" value="{{ old('telefono', $usuario->telefono) }}">
                        @error('telefono')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="rol" class="field-label">Rol</label>
                        <select id="rol" name="rol" class="form-select @error('rol') is-invalid @enderror" required>
                            @if ($usuario->esAdministrador())
                                <option value="admin">Administrador</option>
                            @else
                                <option value="cliente" @selected(old('rol', $usuario->rol) === 'cliente')>Cliente</option>
                                <option value="recepcionista" @selected(old('rol', $usuario->rol) === 'recepcionista')>Recepcionista</option>
                            @endif
                        </select>
                        @error('rol')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="field">
                        <label class="field-label">Estado</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="estado" name="estado" value="1" @checked(old('estado', $usuario->estado))>
                            <label class="form-check-label" for="estado">Usuario activo</label>
                        </div>
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
                    <a href="{{ route('admin.usuarios.index') }}" class="btn btn--ghost">Cancelar</a>
                    <span class="spacer"></span>
                    <button type="submit" class="btn btn--primary">Actualizar usuario</button>
                </div>
            </form>
        </div>
    </div>
@endsection
