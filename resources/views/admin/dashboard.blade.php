@extends('layouts.app')

@section('title', 'Panel operativo')

@section('content')
    <div class="hero">
        <div class="hero-text">
            <span class="eyebrow">Administración · Operación</span>
            <h1 class="hero-title">Panel <span class="accent">operativo</span></h1>
            <p class="hero-sub">Resumen de reservas, ingresos y ocupación de las canchas.</p>
        </div>
        <div class="hero-actions">
            <a href="{{ route('admin.reportes.index') }}" class="btn btn--primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M3 20V4M7 20v-6M11 20v-10M15 20v-4M19 20V8"/></svg>
                Ver reportes
            </a>
        </div>
    </div>

    <!-- KPIs -->
    <div class="kpi-grid">
        <div class="kpi-card c-success">
            <div class="kpi-top">
                <div class="kpi-identity">
                    <div class="kpi-icon success">
                        <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    </div>
                    <span class="kpi-label">Total reservas</span>
                </div>
            </div>
            <div class="kpi-value">{{ $metricas['total_reservas'] }}</div>
            <div class="kpi-compare">
                <span>desde el inicio</span>
            </div>
        </div>

        <div class="kpi-card c-primary">
            <div class="kpi-top">
                <div class="kpi-identity">
                    <div class="kpi-icon primary">
                        <svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg>
                    </div>
                    <span class="kpi-label">Reservas activas</span>
                </div>
            </div>
            <div class="kpi-value">{{ $metricas['reservas_activas'] }}</div>
            <div class="kpi-compare">
                <span>en curso</span>
            </div>
        </div>

        <div class="kpi-card c-purple">
            <div class="kpi-top">
                <div class="kpi-identity">
                    <div class="kpi-icon info">
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                    </div>
                    <span class="kpi-label">Reservas hoy</span>
                </div>
            </div>
            <div class="kpi-value">{{ $metricas['reservas_hoy'] }}<sup>hoy</sup></div>
            <div class="kpi-compare">
                <span>para el día de hoy</span>
            </div>
        </div>

        <div class="kpi-card c-success">
            <div class="kpi-top">
                <div class="kpi-identity">
                    <div class="kpi-icon success">
                        <svg viewBox="0 0 24 24"><rect x="3" y="6" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h4"/></svg>
                    </div>
                    <span class="kpi-label">Ingresos estimados</span>
                </div>
            </div>
            <div class="kpi-value">Bs. {{ number_format($metricas['ingresos_estimados'], 0) }}</div>
            <div class="kpi-compare">
                <span>estimado</span>
            </div>
        </div>

        <div class="kpi-card c-primary">
            <div class="kpi-top">
                <div class="kpi-identity">
                    <div class="kpi-icon warning">
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 6.5 16.8 9.2 15.4 14.6H8.6L7.2 9.2 12 6.5Z"/></svg>
                    </div>
                    <span class="kpi-label">Ocupación hoy</span>
                </div>
            </div>
            <div class="kpi-value">{{ $metricas['ocupacion_hoy'] }}%</div>
            <div class="kpi-compare">
                <span>de la capacidad</span>
            </div>
        </div>

        <div class="kpi-card c-danger">
            <div class="kpi-top">
                <div class="kpi-identity">
                    <div class="kpi-icon purple">
                        <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                    </div>
                    <span class="kpi-label">Clientes</span>
                </div>
            </div>
            <div class="kpi-value">{{ $metricas['total_clientes'] }}</div>
            <div class="kpi-compare">
                <span>registrados</span>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="grid" style="grid-template-columns: 1.6fr 1fr; margin-bottom: 20px;">
        <div class="card">
            <div class="card-head">
                <h3 class="card-title">Reservas activas por día</h3>
                <span class="tag t-info">últimos 14 días</span>
            </div>
            <div class="card-body p-0">
                <canvas id="grafico-dias" height="120"></canvas>
            </div>
        </div>

        <div class="card">
            <div class="card-head">
                <h3 class="card-title">Reservas por estado</h3>
            </div>
            <div class="card-body p-0 d-flex justify-content-center align-items-center">
                <canvas id="grafico-estados" height="160"></canvas>
            </div>
        </div>
    </div>

    <div class="grid" style="grid-template-columns: 1fr 1fr;">
        <div class="card">
            <div class="card-head">
                <h3 class="card-title">Canchas más usadas</h3>
            </div>
            <div class="card-body p-0">
                @if ($canchasMasUsadas->isNotEmpty())
                    <canvas id="grafico-canchas" height="140"></canvas>
                @else
                    <p class="text-muted mb-0">Aún no hay datos para mostrar.</p>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-head">
                <h3 class="card-title">Resumen rápido</h3>
            </div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between">
                    <span>Pendientes</span>
                    <span class="badge bg-warning text-dark">{{ $metricas['reservas_pendientes'] }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>Confirmadas</span>
                    <span class="badge bg-success">{{ $metricas['reservas_confirmadas'] }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>Completadas</span>
                    <span class="badge bg-secondary">{{ $metricas['reservas_completadas'] }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>Canceladas</span>
                    <span class="badge bg-danger">{{ $metricas['reservas_canceladas'] }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>Canchas (disponibles)</span>
                    <span>{{ $metricas['total_canchas'] }} ({{ $metricas['canchas_disponibles'] }})</span>
                </li>
            </ul>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const dias = @json($reservasPorDia);
        const etiquetasDias = Object.keys(dias).map(f => {
            const [y, m, d] = f.split('-');
            return d + '/' + m;
        });
        const valoresDias = Object.values(dias);

        const tokens = getComputedStyle(document.documentElement);

        new Chart(document.getElementById('grafico-dias'), {
            type: 'line',
            data: {
                labels: etiquetasDias,
                datasets: [{
                    label: 'Reservas',
                    data: valoresDias,
                    borderColor: tokens.getPropertyValue('--primary').trim(),
                    backgroundColor: tokens.getPropertyValue('--primary-soft').trim(),
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });

        const estados = @json($reservasPorEstado);
        const t = {
            'Pendiente': tokens.getPropertyValue('--warning').trim(),
            'Confirmada': tokens.getPropertyValue('--success').trim(),
            'Completada': tokens.getPropertyValue('--t-light').trim(),
            'Cancelada': tokens.getPropertyValue('--danger').trim()
        };

        new Chart(document.getElementById('grafico-estados'), {
            type: 'doughnut',
            data: {
                labels: Object.keys(estados),
                datasets: [{
                    data: Object.values(estados),
                    backgroundColor: Object.keys(estados).map(e => t[e] || tokens.getPropertyValue('--info').trim())
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } }
            }
        });

        const canchas = @json($canchasMasUsadas);
        if (canchas.length) {
            new Chart(document.getElementById('grafico-canchas'), {
                type: 'bar',
                data: {
                    labels: canchas.map(c => c.nombre),
                    datasets: [{
                        label: 'Reservas activas',
                        data: canchas.map(c => c.total),
                        backgroundColor: tokens.getPropertyValue('--primary').trim()
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } }
                }
            });
        }
    });
</script>
@endpush
