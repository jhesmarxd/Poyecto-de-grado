@extends('layouts.app')

@section('title', 'Nueva Cancha')

@section('content')
    <div class="hero">
        <div class="hero-text">
            <span class="eyebrow">Canchas · Crear</span>
            <h1 class="hero-title">Nueva <span class="accent">cancha</span></h1>
            <p class="hero-sub">Registra una cancha con su ubicación, capacidad y precio por hora.</p>
        </div>
    </div>

    <div class="grid">
        <div class="card col-8">
            <div class="card-head">
                <div class="card-title-wrap">
                    <span class="eyebrow">Datos de la cancha</span>
                    <h3 class="card-title">Información general</h3>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.canchas.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="form-grid">
                    <div class="field">
                        <label for="nombre" class="field-label">Nombre</label>
                        <input id="nombre" type="text" class="form-control @error('nombre') is-invalid @enderror" name="nombre" value="{{ old('nombre') }}" required autofocus>
                        @error('nombre')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="ubicacion" class="field-label">Ubicación</label>
                        <input id="ubicacion" type="text" class="form-control @error('ubicacion') is-invalid @enderror" name="ubicacion" value="{{ old('ubicacion') }}">
                        @error('ubicacion')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="capacidad" class="field-label">Capacidad</label>
                        <input id="capacidad" type="number" min="1" max="100" class="form-control @error('capacidad') is-invalid @enderror" name="capacidad" value="{{ old('capacidad', 4) }}" required>
                        @error('capacidad')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="precio_por_hora" class="field-label">Precio por hora (Bs.)</label>
                        <input id="precio_por_hora" type="number" step="0.01" min="0" class="form-control @error('precio_por_hora') is-invalid @enderror" name="precio_por_hora" value="{{ old('precio_por_hora') }}" required>
                        @error('precio_por_hora')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="estado" class="field-label">Estado</label>
                        <select id="estado" name="estado" class="form-select @error('estado') is-invalid @enderror" required>
                            @foreach (\App\Models\Cancha::ESTADOS as $valor => $etiqueta)
                                <option value="{{ $valor }}" @selected(old('estado') === $valor)>{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                        @error('estado')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="field col-12">
                        <label for="descripcion" class="field-label">Descripción</label>
                        <textarea id="descripcion" class="form-control @error('descripcion') is-invalid @enderror" name="descripcion" rows="3">{{ old('descripcion') }}</textarea>
                        @error('descripcion')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="tipo_recinto" class="field-label">Tipo de recinto</label>
                        <select id="tipo_recinto" name="tipo_recinto" class="form-select @error('tipo_recinto') is-invalid @enderror" required>
                            <option value="cerrada" @selected(old('tipo_recinto', 'cerrada') === 'cerrada')>Cerrada (techada)</option>
                            <option value="abierta" @selected(old('tipo_recinto') === 'abierta')>Abierta (campo abierto)</option>
                        </select>
                        @error('tipo_recinto')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="color_malla" class="field-label">Color de la malla</label>
                        <input id="color_malla" type="text" class="form-control @error('color_malla') is-invalid @enderror" name="color_malla" value="{{ old('color_malla') }}" placeholder="Ej.: Verde">
                        @error('color_malla')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="altura_malla" class="field-label">Altura de la malla</label>
                        <input id="altura_malla" type="text" class="form-control @error('altura_malla') is-invalid @enderror" name="altura_malla" value="{{ old('altura_malla') }}" placeholder="Ej.: 2 m">
                        @error('altura_malla')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="tipo_piso" class="field-label">Tipo de piso</label>
                        <input id="tipo_piso" type="text" class="form-control @error('tipo_piso') is-invalid @enderror" name="tipo_piso" value="{{ old('tipo_piso') }}" placeholder="Ej.: Sintético">
                        @error('tipo_piso')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="field col-12">
                        <label for="imagen" class="field-label">Imagen de la cancha</label>
                        <input id="imagen" type="file" class="form-control @error('imagen') is-invalid @enderror" name="imagen" accept="image/jpeg,image/png,image/webp">
                        <div class="form-text">JPG, PNG o WebP, hasta 4 MB. Se mostrará también dentro del chatbot.</div>
                        @error('imagen')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>

                <div class="form-actions">
                    <a href="{{ route('admin.canchas.index') }}" class="btn btn--ghost">Cancelar</a>
                    <span class="spacer"></span>
                    <button type="submit" class="btn btn--primary">Guardar cancha</button>
                </div>
            </form>
        </div>
    </div>
@endsection
