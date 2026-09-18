<?php

namespace App\Http\Controllers\Recepcionista;

use App\Http\Controllers\Controller;
use App\Http\Requests\CalendarEventsRequest;
use App\Http\Requests\StoreRecepcionistaReservaRequest;
use App\Models\Cancha;
use App\Models\Reserva;
use App\Models\User;
use App\Services\ReservaCalendarService;
use App\Services\ReservaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReservaController extends Controller
{
    public function __construct(
        private readonly ReservaService $reservaService,
        private readonly ReservaCalendarService $calendarService
    ) {}

    public function calendario(): View
    {
        $canchas = Cancha::orderBy('nombre')->get();

        return view('recepcionista.reservas.calendario', compact('canchas'));
    }

    public function eventos(CalendarEventsRequest $request): JsonResponse
    {
        $datos = $request->validated();
        $timezone = (string) config('app.timezone', 'America/La_Paz');
        $inicio = Carbon::parse($datos['start'], $timezone)->startOfDay();
        $fin = Carbon::parse($datos['end'], $timezone)->startOfDay();

        $eventos = $this->calendarService->paraRecepcionista(
            $inicio,
            $fin,
            isset($datos['cancha_id']) ? (int) $datos['cancha_id'] : null,
            $datos['estado'] ?? null
        );

        return response()->json($eventos)->withHeaders([
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    /**
     * Listado de reservas del día actual.
     */
    public function hoy(): View
    {
        $fecha = now()->toDateString();

        $reservas = Reserva::query()
            ->with(['usuario', 'cancha', 'horario', 'balon'])
            ->whereDate('fecha', $fecha)
            ->join('horarios', 'reservas.horario_id', '=', 'horarios.id')
            ->orderBy('horarios.hora_inicio')
            ->select('reservas.*')
            ->get();

        $resumen = [
            'total' => $reservas->count(),
            'pendientes' => $reservas->where('estado', Reserva::ESTADO_PENDIENTE)->count(),
            'por_ingresar' => $reservas->where('estado', Reserva::ESTADO_CONFIRMADA)
                ->whereNull('check_in_at')->count(),
            'en_curso' => $reservas->filter(fn (Reserva $reserva) => $reserva->estaEnCurso())->count(),
            'finalizadas' => $reservas->whereNotNull('check_out_at')->count(),
        ];

        return view('recepcionista.reservas.hoy', compact('reservas', 'fecha', 'resumen'));
    }

    /**
     * Listado de todas las reservas con filtros.
     */
    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'estado' => ['nullable', Rule::in(array_keys(Reserva::ESTADOS))],
            'fecha' => ['nullable', 'date'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $estado = $filtros['estado'] ?? null;
        $fecha = $filtros['fecha'] ?? null;
        $termino = trim($filtros['q'] ?? '');

        $reservas = Reserva::query()
            ->with(['usuario', 'cancha', 'horario', 'balon'])
            ->when($estado, fn ($q) => $q->where('estado', $estado))
            ->when($fecha, fn ($q) => $q->whereDate('fecha', $fecha))
            ->when($termino, function ($query) use ($termino) {
                $query->where(function ($q) use ($termino) {
                    $q->whereHas('usuario', fn ($usuario) => $usuario
                        ->where('name', 'like', "%{$termino}%")
                        ->orWhere('email', 'like', "%{$termino}%")
                        ->orWhere('telefono', 'like', "%{$termino}%"))
                        ->orWhereHas('cancha', fn ($cancha) => $cancha
                            ->where('nombre', 'like', "%{$termino}%"));
                });
            })
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('recepcionista.reservas.index', compact('reservas', 'estado', 'fecha', 'termino'));
    }

    /**
     * Formulario para crear una reserva presencial.
     */
    public function create(): View
    {
        $canchas = Cancha::disponibles()->orderBy('nombre')->get();
        $clientes = User::query()
            ->where('rol', User::ROL_CLIENTE)
            ->where('estado', true)
            ->orderBy('name')
            ->get();

        return view('recepcionista.reservas.create', compact('canchas', 'clientes'));
    }

    /**
     * Registra una reserva presencial para un cliente.
     */
    public function store(StoreRecepcionistaReservaRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::query()
            ->where('rol', User::ROL_CLIENTE)
            ->where('estado', true)
            ->findOrFail($data['user_id']);
        $reserva = $this->reservaService->crearReserva($user, $data);

        return redirect()->route('recepcionista.reservas.hoy')
            ->with('status', "Reserva #{$reserva->id} registrada correctamente.");
    }

    /**
     * Devuelve las franjas todavía libres para la cancha y fecha elegidas.
     */
    public function horariosDisponibles(Request $request): JsonResponse
    {
        $data = $request->validate([
            'cancha_id' => ['required', 'integer', 'exists:canchas,id'],
            'fecha' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $cancha = Cancha::disponibles()->findOrFail($data['cancha_id']);

        $horarios = $cancha->horariosDisponiblesPara($data['fecha'])
            ->map(fn ($horario) => [
                'id' => $horario->id,
                'hora_inicio' => substr($horario->hora_inicio, 0, 5),
                'hora_fin' => substr($horario->hora_fin, 0, 5),
            ]);

        return response()->json($horarios);
    }

    /**
     * Cambia el estado de una reserva.
     */
    public function cambiarEstado(Request $request, Reserva $reserva): RedirectResponse
    {
        $this->authorize('changeStatus', $reserva);

        $nuevoEstado = $request->validate([
            'estado' => ['required', 'in:'.implode(',', array_keys(Reserva::ESTADOS))],
        ])['estado'];

        $this->reservaService->cambiarEstado($reserva, $nuevoEstado);

        return redirect()->back()
            ->with('status', "Reserva #{$reserva->id} marcada como ".Reserva::ESTADOS[$nuevoEstado].'.');
    }
}
