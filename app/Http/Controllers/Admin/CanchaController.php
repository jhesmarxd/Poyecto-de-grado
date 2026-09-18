<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCanchaRequest;
use App\Http\Requests\UpdateCanchaRequest;
use App\Models\Cancha;
use App\Services\CanchaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CanchaController extends Controller
{
    public function __construct(
        private readonly CanchaService $canchaService
    ) {}

    /**
     * Lista de canchas del sistema.
     */
    public function index(): View
    {
        $canchas = Cancha::withCount('horarios')->orderBy('created_at', 'desc')->get();

        return view('admin.canchas.index', compact('canchas'));
    }

    /**
     * Formulario para crear una cancha.
     */
    public function create(): View
    {
        $this->authorize('create', Cancha::class);

        return view('admin.canchas.create');
    }

    /**
     * Registra una nueva cancha.
     */
    public function store(StoreCanchaRequest $request): RedirectResponse
    {
        $this->authorize('create', Cancha::class);

        Cancha::create($this->canchaService->procesarDatos($request->validated()));

        return redirect()->route('admin.canchas.index')
            ->with('status', 'Cancha creada correctamente.');
    }

    /**
     * Formulario para editar una cancha.
     */
    public function edit(Cancha $cancha): View
    {
        $this->authorize('update', $cancha);

        return view('admin.canchas.edit', compact('cancha'));
    }

    /**
     * Actualiza los datos de una cancha.
     */
    public function update(UpdateCanchaRequest $request, Cancha $cancha): RedirectResponse
    {
        $this->authorize('update', $cancha);

        $cancha->update($this->canchaService->procesarDatos($request->validated(), $cancha));

        return redirect()->route('admin.canchas.index')
            ->with('status', 'Cancha actualizada correctamente.');
    }

    /**
     * Elimina una cancha (sus horarios se eliminan en cascada).
     */
    public function destroy(Cancha $cancha): RedirectResponse
    {
        $this->authorize('delete', $cancha);

        $this->canchaService->eliminarCancha($cancha);

        return redirect()->route('admin.canchas.index')
            ->with('status', 'Cancha eliminada correctamente.');
    }
}
