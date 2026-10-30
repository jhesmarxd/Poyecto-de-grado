<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CambioConfiguracionReserva;
use App\Models\Cancha;
use App\Models\ExcepcionReserva;
use App\Models\ReservaAfectadaConfiguracion;
use App\Services\ConfiguracionReservaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ConfiguracionReservaController extends Controller
{
    public function __construct(
        private readonly ConfiguracionReservaService $servicio,
    ) {}

    public function index(): View
    {
        return view('admin.configuracion-reservas.index', [
            'configuracion' => $this->servicio->snapshot(),
            'canchas' => Cancha::query()->orderBy('nombre')->get(),
            'tiposExcepcion' => ExcepcionReserva::TIPOS,
            'excepciones' => ExcepcionReserva::query()->with('cancha')
                ->where('estado', ExcepcionReserva::ESTADO_APLICADA)
                ->whereDate('fecha', '>=', today())->orderBy('fecha')->get(),
            'cambiosPendientes' => CambioConfiguracionReserva::query()
                ->where('estado', CambioConfiguracionReserva::ESTADO_PENDIENTE)
                ->withCount('reservasAfectadas')->latest()->get(),
        ]);
    }

    public function previewConfiguracion(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'anticipacion_maxima_dias' => ['required', 'integer', 'min:1', 'max:365'],
            'permitir_hoy' => ['nullable', 'boolean'],
            'permitir_futuras' => ['nullable', 'boolean'],
            'duraciones_permitidas' => ['required', 'array', 'min:1'],
            'duraciones_permitidas.*' => ['integer', Rule::in([60, 90, 120])],
            'horarios' => ['required', 'array', 'size:7'],
            'horarios.*.abierto' => ['nullable', 'boolean'],
            'horarios.*.hora_apertura' => ['nullable', 'date_format:H:i'],
            'horarios.*.hora_cierre' => ['nullable', 'date_format:H:i'],
            'motivo' => ['nullable', 'string', 'max:255'],
        ]);
        $datos['permitir_hoy'] = $request->boolean('permitir_hoy');
        $datos['permitir_futuras'] = $request->boolean('permitir_futuras');
        foreach (range(1, 7) as $dia) {
            $datos['horarios'][$dia]['abierto'] = $request->boolean("horarios.{$dia}.abierto");
            if ($datos['horarios'][$dia]['abierto']) {
                $inicio = $datos['horarios'][$dia]['hora_apertura'] ?? null;
                $fin = $datos['horarios'][$dia]['hora_cierre'] ?? null;
                if (! $inicio || ! $fin || $fin <= $inicio) {
                    throw ValidationException::withMessages([
                        "horarios.{$dia}.hora_cierre" => 'En un día abierto, la apertura y cierre son obligatorios y el cierre debe ser posterior.',
                    ]);
                }
                $inicioCarbon = Carbon::createFromFormat('H:i', $inicio);
                $finCarbon = Carbon::createFromFormat('H:i', $fin);
                if (! in_array((int) $inicioCarbon->format('i'), [0, 30], true)
                    || ! in_array((int) $finCarbon->format('i'), [0, 30], true)
                    || ((int) $inicioCarbon->diffInMinutes($finCarbon)) % 30 !== 0) {
                    throw ValidationException::withMessages([
                        "horarios.{$dia}.hora_apertura" => 'La apertura y el cierre deben alinearse en intervalos de 30 minutos.',
                    ]);
                }
            }
        }

        $cambio = $this->servicio->prepararConfiguracion($datos, $request->user());
        if ($cambio->reservasAfectadas->isEmpty()) {
            $this->servicio->aplicar($cambio);

            return redirect()->route('admin.configuracion-reservas.index')
                ->with('status', 'Configuración de reservas actualizada correctamente.');
        }

        return redirect()->route('admin.configuracion-reservas.review', $cambio);
    }

    public function previewExcepcion(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'cancha_id' => ['nullable', 'integer', 'exists:canchas,id'],
            'fecha' => ['required', 'date', 'after_or_equal:today'],
            'tipo' => ['required', Rule::in(ExcepcionReserva::TIPOS)],
            'hora_inicio' => ['nullable', 'date_format:H:i'],
            'hora_fin' => ['nullable', 'date_format:H:i', 'after:hora_inicio'],
            'motivo' => ['required', 'string', 'max:255'],
            'mensaje_cliente' => ['nullable', 'string', 'max:2000'],
        ]);
        if (in_array($datos['tipo'], [ExcepcionReserva::BLOQUEO_PARCIAL, ExcepcionReserva::HORARIO_ESPECIAL], true)
            && (empty($datos['hora_inicio']) || empty($datos['hora_fin']))) {
            throw ValidationException::withMessages(['hora_inicio' => 'Este tipo de excepción requiere hora inicial y final.']);
        }

        $cambio = $this->servicio->prepararExcepcion($datos, $request->user());
        if ($cambio->reservasAfectadas->isEmpty()) {
            $this->servicio->aplicar($cambio);

            return redirect()->route('admin.configuracion-reservas.index')
                ->with('status', 'Excepción aplicada; no se encontraron reservas afectadas.');
        }

        return redirect()->route('admin.configuracion-reservas.review', $cambio);
    }

    public function review(CambioConfiguracionReserva $cambio): View
    {
        $cambio->load(['administrador', 'excepciones.cancha', 'reservasAfectadas.reserva.usuario', 'reservasAfectadas.reserva.cancha']);
        $canchas = Cancha::query()->with(['horarios' => fn ($query) => $query->where('disponible', true)->orderBy('dia_semana')->orderBy('hora_inicio')])->orderBy('nombre')->get();

        return view('admin.configuracion-reservas.review', compact('cambio', 'canchas'));
    }

    public function apply(CambioConfiguracionReserva $cambio): RedirectResponse
    {
        $this->servicio->aplicar($cambio);

        return redirect()->route('admin.configuracion-reservas.index')
            ->with('status', 'Cambio aplicado y clientes afectados notificados dentro del sistema.');
    }

    public function cancel(CambioConfiguracionReserva $cambio): RedirectResponse
    {
        abort_unless($cambio->estado === CambioConfiguracionReserva::ESTADO_PENDIENTE, 422);
        $cambio->excepciones()->update(['estado' => ExcepcionReserva::ESTADO_CANCELADA]);
        $cambio->update(['estado' => CambioConfiguracionReserva::ESTADO_CANCELADO]);

        return redirect()->route('admin.configuracion-reservas.index')->with('status', 'Cambio descartado.');
    }

    public function cancelarReserva(ReservaAfectadaConfiguracion $afectada): RedirectResponse
    {
        $this->servicio->cancelarAfectada($afectada);

        return back()->with('status', 'Reserva cancelada por el complejo y conservada en el historial.');
    }

    public function reprogramarReserva(Request $request, ReservaAfectadaConfiguracion $afectada): RedirectResponse
    {
        $datos = $request->validate([
            'cancha_id' => ['required', 'integer', 'exists:canchas,id'],
            'horario_id' => ['required', 'integer', 'exists:horarios,id'],
            'fecha' => ['required', 'date_format:Y-m-d'],
            'hora_fin' => ['required', 'date_format:H:i'],
        ]);
        $this->servicio->reprogramar($afectada, $datos);

        return back()->with('status', 'Reserva reprogramada correctamente.');
    }
}
