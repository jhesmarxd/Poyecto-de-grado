<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBalonRequest;
use App\Models\Balon;
use App\Services\BalonService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BalonController extends Controller
{
    public function __construct(
        private readonly BalonService $balonService
    ) {}

    /**
     * Lista los balones del catálogo (económicos y premium).
     */
    public function index(): View
    {
        $this->authorize('viewAny', Balon::class);

        $balones = $this->balonService->listarBalones();

        return view('admin.balones.index', compact('balones'));
    }

    /**
     * Formulario para registrar un balón nuevo.
     */
    public function create(): View
    {
        $this->authorize('create', Balon::class);

        return view('admin.balones.create');
    }

    /**
     * Guarda un balón nuevo.
     */
    public function store(StoreBalonRequest $request): RedirectResponse
    {
        $this->authorize('create', Balon::class);

        $data = $this->balonService->procesarDatos($request->validated());
        Balon::create($data);

        return redirect()->route('admin.balones.index')
            ->with('status', 'Balón creado correctamente.');
    }

    /**
     * Formulario para editar un balón.
     */
    public function edit(Balon $balon): View
    {
        $this->authorize('update', $balon);

        return view('admin.balones.edit', compact('balon'));
    }

    /**
     * Actualiza los datos del balón y su imagen (opcional).
     */
    public function update(StoreBalonRequest $request, Balon $balon): RedirectResponse
    {
        $this->authorize('update', $balon);

        $data = $this->balonService->procesarDatos($request->validated(), $balon);
        $balon->update($data);

        return redirect()->route('admin.balones.index')
            ->with('status', 'Balón actualizado correctamente.');
    }

    /**
     * Elimina un balón y su imagen.
     */
    public function destroy(Balon $balon): RedirectResponse
    {
        $this->authorize('delete', $balon);

        $this->balonService->eliminarBalon($balon);

        return redirect()->route('admin.balones.index')
            ->with('status', 'Balón eliminado correctamente.');
    }

}