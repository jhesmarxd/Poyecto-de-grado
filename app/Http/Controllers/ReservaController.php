<?php

namespace App\Http\Controllers;

use App\Http\Requests\CalendarEventsRequest;
use App\Http\Requests\StoreReservaRequest;
use App\Models\Balon;
use App\Models\Cancha;
use App\Models\Horario;
use App\Models\Reserva;
use App\Models\User;
use App\Services\ReservaCalendarService;
use App\Services\ReservaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class ReservaController extends Controller
{
    public function __construct(
        private readonly ReservaService $reservaService,
        private readonly ReservaCalendarService $calendarService
    ) {}

    /**
     * Formulario de creación de una reserva para una cancha,
     * horario y fecha concretos (llegados desde disponibilidad).
     */
    public function crear(Request $request): View
    {
        $cancha = Cancha::disponibles()->findOrFail($request->integer('cancha_id'));
        $horario = $cancha->horarios()->findOrFail($request->integer('horario_id'));
        $fecha = $request->input('fecha');

        abort_if(! $this->reservaService->esFechaValida($fecha), 404);

        $balones = Balon::orderBy('tipo')->orderBy('marca')->orderBy('nombre')->get();
        $balonPremium = Balon::premium();

        $balonesOcupados = $this->reservaService->balonesOcupados($fecha, $horario);

        return view('reservas.crear', compact(
            'cancha',
            'horario',
            'fecha',
            'balones',
            'balonesOcupados',
            'balonPremium'
        ));
    }

    /**
     * Registra la reserva aplicando la regla anti-duplicidad:
     * la misma cancha no puede tener dos reservas activas en la misma
     * fecha y horario, y el balón elegido no puede estar ya reservado
     * en ese mismo día/hora (los balones son compartidos por todo el recinto).
     */
    public function store(StoreReservaRequest $request): RedirectResponse|JsonResponse
    {
        if (! $request->user()->tieneCelularValido()) {
            throw ValidationException::withMessages([
                'telefono' => 'Registra un número de celular válido antes de confirmar la reserva.',
            ]);
        }

        $reserva = $this->reservaService->crearReserva($request->user(), $request->validated());

        if ($request->expectsJson()) {
            return response()->json(['redirect' => route('reservas.mis'), 'reserva_id' => $reserva->id]);
        }

        return redirect()->route('reservas.mis')
            ->with('status', "Reserva #{$reserva->id} registrada correctamente.");
    }

    public function guardarCelular(Request $request): JsonResponse
    {
        $data = $request->validate([
            'telefono' => ['required', 'regex:'.User::CELULAR_REGEX],
        ], [
            'telefono.required' => 'Ingresa tu número de celular.',
            'telefono.regex' => 'Ingresa un celular boliviano válido de 8 dígitos, con 6 o 7 inicial. Puedes usar +591.',
        ]);

        $request->user()->update(['telefono' => $data['telefono']]);

        return response()->json(['telefono' => $request->user()->telefono]);
    }

    /**
     * Listado de reservas del usuario autenticado.
     */
    public function mis(Request $request): View|RedirectResponse
    {
        if ($request->user()->esAdministrador()) {
            return redirect()->route('admin.dashboard');
        }

        if ($request->user()->esRecepcionista()) {
            return redirect()->route('recepcionista.reservas.hoy');
        }

        $vista = $request->string('vista')->toString();
        $vista = $vista === 'listado' ? 'listado' : 'calendario';
        $reservas = $vista === 'listado'
            ? $this->reservaService->reservasDelUsuario($request->user())
            : collect();
        $ahoraServidor = Carbon::now((string) config('app.timezone', 'UTC'));
        $proximaReserva = $this->reservaService->proximaReservaDelCliente(
            $request->user(),
            $ahoraServidor
        );

        return view('reservas.mis', compact('reservas', 'vista', 'proximaReserva', 'ahoraServidor'));
    }

    public function eventos(CalendarEventsRequest $request): JsonResponse
    {
        abort_unless($request->user()->rol === User::ROL_CLIENTE, 403);

        $datos = $request->validated();
        $timezone = (string) config('app.timezone', 'America/La_Paz');
        $inicio = Carbon::parse($datos['start'], $timezone)->startOfDay();
        $fin = Carbon::parse($datos['end'], $timezone)->startOfDay();

        $eventos = $this->calendarService->paraCliente(
            $request->user(),
            $inicio,
            $fin,
            $datos['estado'] ?? null
        );

        return response()->json($eventos)->withHeaders([
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    /**
     * Cancela una reserva propia que aún no se haya cumplido.
     */
    public function cancelar(Request $request, Reserva $reserva): RedirectResponse
    {
        $this->authorize('cancel', $reserva);

        abort_if(! $reserva->esCancelablePorCliente(), 422, 'Esta reserva ya no puede cancelarse.');

        $this->reservaService->cancelarReserva($reserva);

        return redirect()->route('reservas.mis')
            ->with('status', "Reserva #{$reserva->id} cancelada correctamente.");
    }
}
