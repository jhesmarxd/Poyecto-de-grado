<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CalendarEventsRequest;
use App\Http\Requests\CambiarEstadoReservaRequest;
use App\Models\Cancha;
use App\Models\Reserva;
use App\Services\ReservaCalendarService;
use App\Services\ReservaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ReservaController extends Controller
{
    public function __construct(
        private readonly ReservaService $reservaService,
        private readonly ReservaCalendarService $calendarService
    ) {}

    /**
     * Listado de reservas con filtros (estado y cancha).
     */
    public function index(Request $request): View
    {
        $estado = $request->input('estado');
        $canchaId = $request->integer('cancha_id');

        $reservas = $this->reservaService->reservasConFiltros($estado, $canchaId);

        $canchas = Cancha::orderBy('nombre')->get();

        return view('admin.reservas.index', compact('reservas', 'estado', 'canchaId', 'canchas'));
    }

    /**
     * Vista del calendario de reservas con FullCalendar.
     */
    public function calendario(): View
    {
        $canchas = Cancha::orderBy('nombre')->get();

        return view('admin.reservas.calendario', compact('canchas'));
    }

    /**
     * Cambia el estado de una reserva (confirmar, cancelar, completar).
     */
    public function cambiarEstado(CambiarEstadoReservaRequest $request, Reserva $reserva): RedirectResponse
    {
        $this->authorize('changeStatus', $reserva);

        $nuevoEstado = $request->validated()['estado'];
        $this->reservaService->cambiarEstado($reserva, $nuevoEstado);

        return redirect()->back()->with('status', "Reserva #{$reserva->id} marcada como ".Reserva::ESTADOS[$nuevoEstado].'.');
    }

    /**
     * Elimina una reserva del registro.
     */
    public function destroy(Request $request, Reserva $reserva): RedirectResponse
    {
        $this->authorize('delete', $reserva);

        $this->reservaService->eliminarReserva($reserva);

        return redirect()->back()->with('status', "Reserva #{$reserva->id} eliminada correctamente.");
    }

    /**
     * Eventos de reservas para FullCalendar.
     */
    public function eventos(CalendarEventsRequest $request): JsonResponse
    {
        $datos = $request->validated();
        $timezone = (string) config('app.timezone', 'America/La_Paz');
        $inicio = Carbon::parse($datos['start'], $timezone)->startOfDay();
        $fin = Carbon::parse($datos['end'], $timezone)->startOfDay();

        $eventos = $this->calendarService->paraAdministrador(
            $inicio,
            $fin,
            isset($datos['cancha_id']) ? (int) $datos['cancha_id'] : null,
            $datos['estado'] ?? null
        );

        return response()->json($eventos)->withHeaders($this->calendarHeaders());
    }

    private function calendarHeaders(): array
    {
        return [
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
        ];
    }
}
