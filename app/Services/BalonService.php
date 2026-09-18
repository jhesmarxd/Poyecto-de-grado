<?php

namespace App\Services;

use App\Models\Balon;
use Illuminate\Http\UploadedFile;

class BalonService
{
    /**
     * Sube la imagen de un balón y devuelve el nombre del archivo.
     * Si ya existía una imagen anterior, la elimina.
     */
    public function subirImagen(UploadedFile $archivo, string $tipo, ?Balon $balonExistente = null): string
    {
        $nombre = 'balon-' . $tipo . '-' . time() . '.' . $archivo->extension();
        $archivo->move(public_path('img/balones'), $nombre);

        // Eliminar imagen anterior si existe
        if ($balonExistente && $balonExistente->imagen) {
            $rutaAnterior = public_path('img/balones/' . $balonExistente->imagen);
            if (file_exists($rutaAnterior)) {
                unlink($rutaAnterior);
            }
        }

        return $nombre;
    }

    /**
     * Elimina la imagen de un balón del disco.
     */
    public function eliminarImagen(?string $imagen): void
    {
        if ($imagen) {
            $ruta = public_path('img/balones/' . $imagen);
            if (file_exists($ruta)) {
                unlink($ruta);
            }
        }
    }

    /**
     * Al marcar un balón como destacado, los demás de su tipo dejan de serlo.
     */
    public function quitarDestacadoDelTipo(string $tipo, ?int $excepto = null): void
    {
        Balon::where('tipo', $tipo)
            ->when($excepto, fn ($q) => $q->where('id', '!=', $excepto))
            ->update(['destacado' => false]);
    }

    /**
     * Procesa los datos de un balón incluyendo imagen y destacado.
     *
     * @param array<string, mixed> $data Datos validados
     */
    public function procesarDatos(array $data, ?Balon $balon = null): array
    {
        // Manejar imagen si se proporcionó
        if (isset($data['imagen']) && $data['imagen'] instanceof UploadedFile) {
            $data['imagen'] = $this->subirImagen($data['imagen'], $data['tipo'], $balon);
        } else {
            unset($data['imagen']);
        }

        // Manejar destacado
        if (! empty($data['destacado'])) {
            $this->quitarDestacadoDelTipo($data['tipo'], $balon?->id);
        }

        return $data;
    }

    /**
     * Elimina un balón y su imagen.
     */
    public function eliminarBalon(Balon $balon): void
    {
        $this->eliminarImagen($balon->imagen);
        $balon->delete();
    }

    /**
     * Obtiene todos los balones ordenados por tipo, marca y nombre.
     */
    public function listarBalones()
    {
        return Balon::orderBy('tipo')
            ->orderBy('marca')
            ->orderBy('nombre')
            ->get();
    }
}
