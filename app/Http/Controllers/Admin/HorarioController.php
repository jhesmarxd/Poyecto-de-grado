<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHorarioRequest;
use App\Http\Requests\UpdateHorarioRequest;
use App\Models\Cancha;
use App\Models\CierreCancha;
use App\Models\ExcepcionReserva;
use App\Models\Horario;
use App\Services\ConfiguracionReservaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class HorarioController extends Controller
{
    public function __construct(
        private readonly ConfiguracionReservaService $configuracionReservas,
    ) {}

    /**
     * Lista de horarios de una cancha (con selector de cancha).
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Horario::class);

        $canchas = Cancha::orderBy('nombre')->get();
        $canchaId = $request->integer('cancha_id');
        $cancha = $canchas->firstWhere('id', $canchaId) ?? $canchas->first();

        $horarios = $cancha
            ? Horario::where('cancha_id', $cancha->id)
                ->orderBy('dia_semana')
                ->orderBy('hora_inicio')
                ->get()
            : collect();

        $cierres = $cancha
            ? $cancha->cierres()->whereDate('fecha', '>=', today())->orderBy('fecha')->get()
            : collect();

        return view('admin.horarios.index', compact('canchas', 'cancha', 'horarios', 'cierres'));
    }

    /**
     * Formulario para agregar un horario a una cancha.
     */
    public function create(Cancha $cancha): View
    {
        $this->authorize('create', Horario::class);

        return view('admin.horarios.create', compact('cancha'));
    }

    /**
     * Registra un nuevo horario para la cancha.
     */
    public function store(StoreHorarioRequest $request, Cancha $cancha): RedirectResponse
    {
        $this->authorize('create', Horario::class);

        $data = $request->validated();
        $data['hora_inicio'] .= ':00';
        $data['hora_fin'] .= ':00';
        $data['disponible'] = $request->boolean('disponible');

        $cancha->horarios()->create($data);

        return redirect()->route('admin.horarios.index', ['cancha_id' => $cancha->id])
            ->with('status', 'Horario creado correctamente.');
    }

    /**
     * Genera franjas consecutivas para varios días dentro de un periodo.
     */
    public function storeBulk(Request $request, Cancha $cancha): RedirectResponse
    {
        $this->authorize('create', Horario::class);

        $data = $request->validate([
            'dias' => ['required', 'array', 'min:1'],
            'dias.*' => ['integer', 'min:1', 'max:7'],
            'vigente_desde' => ['required', 'date'],
            'vigente_hasta' => ['required', 'date', 'after_or_equal:vigente_desde'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'duracion' => ['required', 'integer', 'in:30,60,90,120'],
        ]);

        $inicio = Carbon::createFromFormat('H:i', $data['hora_inicio']);
        $fin = Carbon::createFromFormat('H:i', $data['hora_fin']);
        $duracionTotal = $inicio->diffInMinutes($fin);
        if ($duracionTotal % (int) $data['duracion'] !== 0) {
            throw ValidationException::withMessages([
                'duracion' => 'El rango debe dividirse exactamente en franjas de la duración seleccionada.',
            ]);
        }

        DB::transaction(function () use ($cancha, $data, $inicio, $fin): void {
            foreach (array_unique($data['dias']) as $dia) {
                for ($cursor = $inicio->copy(); $cursor->lt($fin); $cursor->addMinutes((int) $data['duracion'])) {
                    $finFranja = $cursor->copy()->addMinutes((int) $data['duracion']);
                    $horaInicio = $cursor->format('H:i:s');
                    $horaFin = $finFranja->format('H:i:s');

                    $exacta = $cancha->horarios()
                        ->where('dia_semana', $dia)
                        ->where('hora_inicio', $horaInicio)
                        ->where('hora_fin', $horaFin)
                        ->first();

                    if ($exacta) {
                        $exacta->update([
                            'vigente_desde' => $data['vigente_desde'],
                            'vigente_hasta' => $data['vigente_hasta'],
                            'disponible' => true,
                        ]);

                        continue;
                    }

                    $superpuesta = $cancha->horarios()
                        ->where('dia_semana', $dia)
                        ->where('hora_inicio', '<', $horaFin)
                        ->where('hora_fin', '>', $horaInicio)
                        ->where(fn ($query) => $query
                            ->whereNull('vigente_desde')
                            ->orWhereDate('vigente_desde', '<=', $data['vigente_hasta']))
                        ->where(fn ($query) => $query
                            ->whereNull('vigente_hasta')
                            ->orWhereDate('vigente_hasta', '>=', $data['vigente_desde']))
                        ->exists();

                    if ($superpuesta) {
                        throw ValidationException::withMessages([
                            'hora_inicio' => 'La configuración se superpone con una franja diferente ya registrada.',
                        ]);
                    }

                    $cancha->horarios()->create([
                        'dia_semana' => $dia,
                        'vigente_desde' => $data['vigente_desde'],
                        'vigente_hasta' => $data['vigente_hasta'],
                        'hora_inicio' => $horaInicio,
                        'hora_fin' => $horaFin,
                        'disponible' => true,
                    ]);
                }
            }
        });

        return redirect()->route('admin.horarios.index', ['cancha_id' => $cancha->id])
            ->with('status', 'Configuración semanal aplicada correctamente.');
    }

    public function storeClosure(Request $request, Cancha $cancha): RedirectResponse
    {
        $this->authorize('create', Horario::class);
        $data = $request->validate([
            'fecha_cierre' => ['required', 'date', 'after_or_equal:today'],
            'motivo' => ['nullable', 'string', 'max:255'],
        ]);

        $cambio = $this->configuracionReservas->prepararExcepcion([
            'cancha_id' => $cancha->id,
            'fecha' => $data['fecha_cierre'],
            'tipo' => ExcepcionReserva::CIERRE_COMPLETO,
            'motivo' => $data['motivo'] ?? 'Cierre excepcional',
            'mensaje_cliente' => null,
        ], $request->user());

        if ($cambio->reservasAfectadas->isNotEmpty()) {
            return redirect()->route('admin.configuracion-reservas.review', $cambio);
        }

        $this->configuracionReservas->aplicar($cambio);

        return back()->with('status', 'Cierre excepcional registrado correctamente.');
    }

    public function destroyClosure(Cancha $cancha, CierreCancha $cierre): RedirectResponse
    {
        $this->authorize('viewAny', Horario::class);
        abort_unless($cierre->cancha_id === $cancha->id, 404);
        $cierre->delete();

        return back()->with('status', 'Cierre excepcional eliminado correctamente.');
    }

    /**
     * Formulario para editar un horario.
     */
    public function edit(Cancha $cancha, Horario $horario): View
    {
        $this->authorize('update', $horario);

        return view('admin.horarios.edit', compact('cancha', 'horario'));
    }

    /**
     * Actualiza un horario.
     */
    public function update(UpdateHorarioRequest $request, Cancha $cancha, Horario $horario): RedirectResponse
    {
        $this->authorize('update', $horario);
        abort_if(
            $horario->reservas()->activas()->whereDate('fecha', '>=', today())->exists(),
            422,
            'Esta franja tiene reservas futuras. Realiza el cambio desde Reglas de reservas y chatbot para revisar los conflictos.'
        );

        $data = $request->validated();
        $data['hora_inicio'] .= ':00';
        $data['hora_fin'] .= ':00';
        $data['disponible'] = $request->boolean('disponible');

        $horario->update($data);

        return redirect()->route('admin.horarios.index', ['cancha_id' => $cancha->id])
            ->with('status', 'Horario actualizado correctamente.');
    }

    /**
     * Elimina un horario.
     */
    public function destroy(Cancha $cancha, Horario $horario): RedirectResponse
    {
        $this->authorize('delete', $horario);
        abort_if(
            $horario->reservas()->activas()->whereDate('fecha', '>=', today())->exists(),
            422,
            'Esta franja tiene reservas futuras y no puede eliminarse directamente.'
        );

        $horario->delete();

        return redirect()->route('admin.horarios.index', ['cancha_id' => $cancha->id])
            ->with('status', 'Horario eliminado correctamente.');
    }
}
