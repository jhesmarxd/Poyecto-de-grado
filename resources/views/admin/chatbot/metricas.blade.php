@extends('layouts.app')

@section('title', 'Métricas del chatbot')

@section('content')
    @include('admin._analysis_navigation')

    <div class="hero">
        <div class="hero-text">
            <span class="eyebrow">Inteligencia y reportes</span>
            <h1 class="hero-title">Métricas del <span class="accent">chatbot</span></h1>
            <p class="hero-sub">Uso, seguridad y conversiones reales durante {{ strtolower($metricas['periodo_label']) }}.</p>
        </div>
        <div class="hero-actions flex-wrap">
            @foreach ($periodos as $valor => $etiqueta)
                <a href="{{ route('admin.chatbot.metricas', ['periodo' => $valor]) }}"
                   class="btn {{ $periodo === $valor ? 'btn--primary' : 'btn--ghost' }}">
                    {{ $etiqueta }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="kpi-grid">
        <div class="kpi-card c-primary">
            <div class="kpi-top"><span class="kpi-label">Conversaciones activas</span></div>
            <div class="kpi-value">{{ $metricas['total_conversaciones'] }}</div>
            <div class="kpi-compare"><span>con mensajes en el período</span></div>
        </div>
        <div class="kpi-card c-purple">
            <div class="kpi-top"><span class="kpi-label">Mensajes recibidos</span></div>
            <div class="kpi-value">{{ $metricas['total_mensajes'] }}</div>
            <div class="kpi-compare"><span>{{ $metricas['mensajes_por_conversacion'] }} por conversación</span></div>
        </div>
        <div class="kpi-card c-success">
            <div class="kpi-top"><span class="kpi-label">Usuarios identificados</span></div>
            <div class="kpi-value">{{ $metricas['usuarios_identificados'] }}</div>
            <div class="kpi-compare"><span>{{ $metricas['conversaciones_visitantes'] }} conversaciones de visitantes</span></div>
        </div>
        <div class="kpi-card c-success">
            <div class="kpi-top"><span class="kpi-label">Reservas generadas</span></div>
            <div class="kpi-value">{{ $metricas['reservas_creadas'] }}</div>
            <div class="kpi-compare"><strong>{{ $metricas['tasa_conversion'] }}%</strong><span>de conversión</span></div>
        </div>
        <div class="kpi-card c-danger">
            <div class="kpi-top"><span class="kpi-label">Consultas bloqueadas</span></div>
            <div class="kpi-value">{{ $metricas['consultas_bloqueadas'] }}</div>
            <div class="kpi-compare"><strong>{{ $metricas['tasa_bloqueo'] }}%</strong><span>de los mensajes</span></div>
        </div>
        <div class="kpi-card c-danger">
            <div class="kpi-top"><span class="kpi-label">No resueltas</span></div>
            <div class="kpi-value">{{ $metricas['consultas_no_resueltas'] }}</div>
            <div class="kpi-compare"><strong>{{ $metricas['tasa_no_resueltas'] }}%</strong><span>de los mensajes</span></div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-head">
            <div class="card-title-wrap">
                <span class="eyebrow">Observabilidad Fase 1</span>
                <h2 class="card-title">Motor de procesamiento</h2>
            </div>
            <span class="tag t-info">{{ $metricas['observabilidad']['total'] }} ejecuciones auditadas</span>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-6 col-lg">
                    <div class="small text-muted">IA externa utilizada</div>
                    <strong>{{ $metricas['observabilidad']['uso_externo'] }}</strong>
                    <span class="small text-muted">· {{ $metricas['observabilidad']['tasa_uso_externo'] }}%</span>
                </div>
                <div class="col-6 col-lg">
                    <div class="small text-muted">Intentos externos</div>
                    <strong>{{ $metricas['observabilidad']['intentos_externos'] }}</strong>
                </div>
                <div class="col-6 col-lg">
                    <div class="small text-muted">Fallback local</div>
                    <strong>{{ $metricas['observabilidad']['fallbacks'] }}</strong>
                </div>
                <div class="col-6 col-lg">
                    <div class="small text-muted">Errores externos</div>
                    <strong>{{ $metricas['observabilidad']['errores_externos'] }}</strong>
                </div>
                <div class="col-6 col-lg">
                    <div class="small text-muted">Latencia externa promedio</div>
                    <strong>{{ $metricas['observabilidad']['latencia_promedio_ms'] }} ms</strong>
                </div>
            </div>
        </div>
    </div>

    <div class="grid" style="grid-template-columns: 1.45fr 1fr; margin-bottom: 20px;">
        <div class="card">
            <div class="card-head">
                <div class="card-title-wrap">
                    <span class="eyebrow">Demanda reciente</span>
                    <h2 class="card-title">Mensajes por día</h2>
                </div>
                <span class="tag t-info">últimos {{ count($metricas['actividad']) }} días</span>
            </div>
            <div class="card-body p-0">
                <canvas id="chatbot-actividad" height="125"></canvas>
            </div>
        </div>

        <div class="card">
            <div class="card-head">
                <div class="card-title-wrap">
                    <span class="eyebrow">Temas consultados</span>
                    <h2 class="card-title">Intenciones principales</h2>
                </div>
            </div>
            <div class="card-body p-0">
                @forelse ($metricas['intenciones'] as $intencion)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between gap-3 mb-1 small">
                            <span>{{ $intencion['etiqueta'] }}</span>
                            <strong>{{ $intencion['total'] }} · {{ $intencion['porcentaje'] }}%</strong>
                        </div>
                        <div class="progress" role="progressbar" aria-label="{{ $intencion['etiqueta'] }}" aria-valuenow="{{ $intencion['porcentaje'] }}" aria-valuemin="0" aria-valuemax="100" style="height: 6px;">
                            <div class="progress-bar" style="width: {{ $intencion['porcentaje'] }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted mb-0">Aún no hay mensajes en este período.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="alert alert-info mb-0" role="note">
        La conversión se registra cuando el chatbot crea efectivamente una reserva. No se infiere a partir de palabras ni se incluyen reservas creadas desde otros formularios.
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const actividad = @json($metricas['actividad']);
        const canvas = document.getElementById('chatbot-actividad');
        if (!canvas || !window.Chart) return;

        const tokens = getComputedStyle(document.documentElement);
        new Chart(canvas, {
            type: 'line',
            data: {
                labels: Object.keys(actividad).map(fecha => {
                    const [, mes, dia] = fecha.split('-');
                    return dia + '/' + mes;
                }),
                datasets: [{
                    data: Object.values(actividad),
                    borderColor: tokens.getPropertyValue('--primary').trim(),
                    backgroundColor: tokens.getPropertyValue('--primary-soft').trim(),
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    });
</script>
@endpush
