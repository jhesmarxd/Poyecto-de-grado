@extends('layouts.app')

@section('title', 'Nueva Cancha')

@section('content')
    <div class="hero py-2 mb-2">
        <div class="hero-text">
            <span class="eyebrow" style="font-size: 0.8rem;">Canchas · Crear</span>
            <h1 class="hero-title" style="font-size: 1.6rem; margin-bottom: 0;">Nueva <span class="accent">cancha</span></h1>
            <p class="hero-sub" style="font-size: 0.9rem; margin-bottom: 0;">Registra una cancha con su ubicación y precio por hora.</p>
        </div>
    </div>

    <div class="grid">
        <div class="card col-12" style="padding: 1rem 1.5rem;">
            <div class="card-head mb-2">
                <div class="card-title-wrap">
                    <span class="eyebrow">Datos de la cancha</span>
                    <h3 class="card-title" style="font-size: 1.2rem; margin: 0;">Información general</h3>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.canchas.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="row g-2">
                    
                    <!-- Fila 1 -->
                    <div class="col-md-4 field">
                        <label for="nombre" class="field-label mb-1">Nombre</label>
                        <input id="nombre" type="text" class="form-control form-control-sm @error('nombre') is-invalid @enderror" name="nombre" value="{{ old('nombre') }}" required autofocus>
                        @error('nombre')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="col-md-4 field">
                        <label for="ubicacion" class="field-label mb-1">Ubicación</label>
                        <input id="ubicacion" type="text" class="form-control form-control-sm @error('ubicacion') is-invalid @enderror" name="ubicacion" value="{{ old('ubicacion') }}">
                        @error('ubicacion')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="col-md-4 field">
                        <label for="precio_por_hora" class="field-label mb-1">Precio por hora (Bs.)</label>
                        <input id="precio_por_hora" type="number" step="0.01" min="0" class="form-control form-control-sm @error('precio_por_hora') is-invalid @enderror" name="precio_por_hora" value="{{ old('precio_por_hora') }}" required>
                        @error('precio_por_hora')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <!-- Fila 2 -->
                    <div class="col-md-4 field">
                        <label for="estado" class="field-label mb-1">Estado</label>
                        <select id="estado" name="estado" class="form-select form-select-sm @error('estado') is-invalid @enderror" required>
                            @foreach (\App\Models\Cancha::ESTADOS as $valor => $etiqueta)
                                <option value="{{ $valor }}" @selected(old('estado') === $valor)>{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                        @error('estado')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="col-md-4 field">
                        <label for="tipo_recinto" class="field-label mb-1">Tipo de recinto</label>
                        <select id="tipo_recinto" name="tipo_recinto" class="form-select form-select-sm @error('tipo_recinto') is-invalid @enderror" required>
                            <option value="cerrada" @selected(old('tipo_recinto', 'cerrada') === 'cerrada')>Cerrada (techada)</option>
                            <option value="abierta" @selected(old('tipo_recinto') === 'abierta')>Abierta (campo abierto)</option>
                        </select>
                        @error('tipo_recinto')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="col-md-4 field">
                        <label for="color_malla" class="field-label mb-1">Color de la malla</label>
                        <input id="color_malla" type="text" class="form-control form-control-sm @error('color_malla') is-invalid @enderror" name="color_malla" value="{{ old('color_malla') }}" placeholder="Ej.: Verde">
                        @error('color_malla')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <!-- Fila 3 -->
                    <div class="col-md-4 field">
                        <label for="altura_malla" class="field-label mb-1">Altura de la malla</label>
                        <input id="altura_malla" type="text" class="form-control form-control-sm @error('altura_malla') is-invalid @enderror" name="altura_malla" value="{{ old('altura_malla') }}" placeholder="Ej.: 2 m">
                        @error('altura_malla')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="col-md-4 field">
                        <label for="tipo_piso" class="field-label mb-1">Tipo de piso</label>
                        <input id="tipo_piso" type="text" class="form-control form-control-sm @error('tipo_piso') is-invalid @enderror" name="tipo_piso" value="{{ old('tipo_piso') }}" placeholder="Ej.: Sintético">
                        @error('tipo_piso')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="col-md-4 field">
                        <label for="descripcion" class="field-label mb-1">Descripción</label>
                        <textarea id="descripcion" class="form-control form-control-sm @error('descripcion') is-invalid @enderror" name="descripcion" rows="2" placeholder="Opcional">{{ old('descripcion') }}</textarea>
                        @error('descripcion')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <!-- Imagen -->
                    <div class="col-md-12 field mt-1">
                        <label for="imagen" class="field-label mb-1">Imagen de la cancha</label>
                        <input id="imagen" type="file" class="form-control form-control-sm @error('imagen') is-invalid @enderror" name="imagen" accept="image/jpeg,image/png,image/webp">
                        <div class="form-text" style="font-size: 0.75rem;">JPG, PNG o WebP, hasta 4 MB.</div>
                        @error('imagen')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                </div>

                <!-- Botones centrados con clases estándar de Bootstrap -->
                <div class="d-flex justify-content-center gap-3 mt-3">
                    <a href="{{ route('admin.canchas.index') }}" class="btn btn--ghost btn-sm px-4">Cancelar</a>
                    <button type="submit" class="btn btn--primary btn-sm px-4">Guardar cancha</button>
                </div>
            </form>
        </div>
    </div>
@endsection