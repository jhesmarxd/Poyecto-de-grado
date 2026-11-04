@props(['canchas', 'selected' => null, 'id' => 'cancha_id', 'placeholder' => 'Selecciona una cancha…'])

@php($selectedCancha = $canchas->firstWhere('id', (int) $selected))
<details class="cancha-picker" data-cancha-picker>
    <summary id="{{ $id }}" class="cancha-picker__trigger" aria-label="Elegir cancha">
        <i class="bi bi-grid-3x2" aria-hidden="true"></i>
        <span data-cancha-picker-label>{{ $selectedCancha?->nombre ?? $placeholder }}</span>
        <i class="bi bi-chevron-down" aria-hidden="true"></i>
    </summary>
    <div class="cancha-picker__panel" role="group" aria-label="Canchas disponibles">
        @forelse ($canchas as $cancha)
            <label class="cancha-picker__card">
                <input type="radio" name="cancha_id" value="{{ $cancha->id }}" required
                       @checked((int) $selected === $cancha->id) data-cancha-name="{{ $cancha->nombre }}">
                <span class="cancha-picker__image">
                    @if ($cancha->imagenUrl())
                        <img src="{{ $cancha->imagenUrl() }}" alt="" loading="lazy">
                    @else
                        <i class="bi bi-image" aria-hidden="true"></i>
                    @endif
                </span>
                <span class="cancha-picker__info">
                    <strong>{{ $cancha->nombre }}</strong>
                    <span class="cancha-picker__description">{{ $cancha->descripcion ?: 'Cancha disponible para jugar.' }}</span>
                    <span class="cancha-picker__features">
                        @if($cancha->color_malla) Malla: {{ $cancha->color_malla }} · @endif
                        @if($cancha->altura_malla) Altura: {{ $cancha->altura_malla }} · @endif
                        @if($cancha->tipo_piso) Piso: {{ $cancha->tipo_piso }} · @endif
                        @if($cancha->tipo_recinto) Recinto: {{ $cancha->tipo_recinto }} @endif
                    </span>
                    <small>Bs. {{ number_format($cancha->precio_por_hora, 2) }} / hora</small>
                </span>
            </label>
        @empty
            <p class="cancha-picker__empty">No hay canchas disponibles en este momento.</p>
        @endforelse
    </div>
</details>
