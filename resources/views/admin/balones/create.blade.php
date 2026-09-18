@extends('layouts.app')

@section('title', 'Nuevo Balón')

@section('content')
    <div class="hero">
        <div class="hero-text">
            <span class="eyebrow">Balones · Nuevo</span>
            <h1 class="hero-title">Registrar un <span class="accent">nuevo balón</span></h1>
            <p class="hero-sub">Completa los datos del balón: marca, color, calidad, precio y foto.</p>
        </div>
    </div>

    <div class="grid">
        <div class="card col-8">
            <div class="card-head">
                <div class="card-title-wrap">
                    <span class="eyebrow">Datos del balón</span>
                    <h3 class="card-title">Información general</h3>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.balones.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="form-grid">
                    <div class="field">
                        <label for="tipo" class="field-label">Tipo de balón</label>
                        <select id="tipo" class="form-select @error('tipo') is-invalid @enderror" name="tipo" required>
                            <option value="">Selecciona…</option>
                            @foreach (\App\Models\Balon::TIPOS as $valor => $etiqueta)
                                <option value="{{ $valor }}" @selected(old('tipo') === $valor)>{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                        @error('tipo')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="marca" class="field-label">Marca</label>
                        <input id="marca" type="text" class="form-control @error('marca') is-invalid @enderror" name="marca" value="{{ old('marca') }}" placeholder="Ej.: Mikasa, Molten, Asatex…">
                        @error('marca')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="nombre" class="field-label">Modelo / nombre</label>
                        <input id="nombre" type="text" class="form-control @error('nombre') is-invalid @enderror" name="nombre" value="{{ old('nombre') }}" placeholder="Ej.: V200W, 5500, 1…" required>
                        @error('nombre')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="color" class="field-label">Color</label>
                        <input id="color" type="text" class="form-control @error('color') is-invalid @enderror" name="color" value="{{ old('color') }}" placeholder="Ej.: Blanco/azul">
                        @error('color')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="calidad" class="field-label">Calidad</label>
                        <input id="calidad" type="text" class="form-control @error('calidad') is-invalid @enderror" name="calidad" value="{{ old('calidad') }}" placeholder="Ej.: Estándar, Alta, Profesional">
                        @error('calidad')
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

                    <div class="field col-12">
                        <label for="imagen" class="field-label">Foto del balón</label>
                        <input id="imagen" type="file" class="form-control @error('imagen') is-invalid @enderror" name="imagen" accept="image/*">
                        <div class="form-text">Formatos JPG, PNG o WEBP (máx. 4 MB). Se muestra en la página pública en tiempo real.</div>
                        @error('imagen')
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

                    <div class="field col-12">
                        <div class="form-check">
                            <input id="destacado" type="checkbox" class="form-check-input @error('destacado') is-invalid @enderror"
                                   name="destacado" value="1" @checked(old('destacado'))>
                            <label for="destacado" class="form-check-label">
                                Balón destacado de su tipo (define la tarifa de referencia para las reservas)
                            </label>
                            @error('destacado')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="{{ route('admin.balones.index') }}" class="btn btn--ghost">Cancelar</a>
                    <span class="spacer"></span>
                    <button type="submit" class="btn btn--primary">Guardar balón</button>
                </div>
            </form>
        </div>
    </div>
@endsection