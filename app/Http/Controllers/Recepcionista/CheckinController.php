<?php

namespace App\Http\Controllers\Recepcionista;

use App\Http\Controllers\Controller;
use App\Models\Reserva;
use App\Services\ReservaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CheckinController extends Controller
{
    public function __construct(
        private readonly ReservaService $reservaService
    ) {}

    /**
     * Mantiene compatibles los accesos anteriores y los lleva a la agenda.
     */
    public function index(): RedirectResponse
    {
        return redirect()->route('recepcionista.reservas.hoy', ['accion' => 'checkin']);
    }

    /**
     * Registra la llegada del cliente.
     */
    public function checkin(Request $request, Reserva $reserva): RedirectResponse
    {
        $this->authorize('changeStatus', $reserva);
        $this->reservaService->registrarCheckIn($reserva);

        return redirect()->route('recepcionista.reservas.hoy')
            ->with('status', "Entrada de la reserva #{$reserva->id} registrada correctamente.");
    }

    /**
     * Mantiene compatibles los accesos anteriores y los lleva a la agenda.
     */
    public function checkoutIndex(): RedirectResponse
    {
        return redirect()->route('recepcionista.reservas.hoy', ['accion' => 'checkout']);
    }

    /**
     * Registra la salida del cliente y completa la reserva.
     */
    public function checkout(Request $request, Reserva $reserva): RedirectResponse
    {
        $this->authorize('changeStatus', $reserva);
        $this->reservaService->registrarCheckOut($reserva);

        return redirect()->route('recepcionista.reservas.hoy')
            ->with('status', "Salida de la reserva #{$reserva->id} registrada correctamente.");
    }
}
