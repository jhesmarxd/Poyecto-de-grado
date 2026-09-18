@extends('layouts.app')

@section('title', 'Clientes y atención')

@section('content')
@include('recepcionista._navigation')

    <div class="hero">
        <div class="hero-text">
            <span class="eyebrow">Recepción · Atención</span>
            <h1 class="hero-title">Clientes y <span class="accent">atención</span></h1>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="GET" class="mb-4">
                <div class="input-group">
                    <input type="text" name="q" class="form-control" placeholder="Buscar por nombre, email o teléfono..." value="{{ $termino ?? '' }}">
                    <button type="submit" class="btn btn-primary">Buscar</button>
                </div>
            </form>

            @if ($clientes->count() > 0)
                <div class="table-scroll">
                    <table class="table table-striped table-hover align-middle">
                        <thead>
                            <tr>
                                <th>N.º</th>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Teléfono</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($clientes as $cliente)
                                <tr>
                                    <td>{{ $clientes->firstItem() + $loop->index }}</td>
                                    <td>{{ $cliente->name }}</td>
                                    <td>{{ $cliente->email }}</td>
                                    <td>{{ $cliente->telefono ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $clientes->withQueryString()->links() }}
            @elseif ($termino)
                <p class="text-muted text-center">No se encontraron clientes con "{{ $termino }}".</p>
            @endif
        </div>
    </div>
@endsection
