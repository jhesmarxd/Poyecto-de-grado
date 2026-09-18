<?php

namespace App\Services;

use App\Models\Cancha;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class CanchaService
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function procesarDatos(array $data, ?Cancha $cancha = null): array
    {
        if (! isset($data['imagen']) || ! $data['imagen'] instanceof UploadedFile) {
            unset($data['imagen']);

            return $data;
        }

        $imagenAnterior = $cancha?->imagen;
        $data['imagen'] = $this->subirImagen($data['imagen'], (string) $data['nombre']);

        if ($imagenAnterior) {
            $this->eliminarImagenSiNoSeUsa($imagenAnterior, $cancha?->id);
        }

        return $data;
    }

    public function eliminarCancha(Cancha $cancha): void
    {
        $imagen = $cancha->imagen;
        $id = $cancha->id;
        $cancha->delete();
        $this->eliminarImagenSiNoSeUsa($imagen, $id);
    }

    private function subirImagen(UploadedFile $archivo, string $nombreCancha): string
    {
        $nombre = 'cancha-'.Str::slug($nombreCancha).'-'.Str::uuid().'.'.$archivo->extension();
        $archivo->move(public_path('img/canchas'), $nombre);

        return $nombre;
    }

    private function eliminarImagenSiNoSeUsa(?string $imagen, ?int $excepto = null): void
    {
        if (! $imagen) {
            return;
        }

        $enUso = Cancha::query()
            ->where('imagen', $imagen)
            ->when($excepto, fn ($query) => $query->whereKeyNot($excepto))
            ->exists();

        $ruta = public_path('img/canchas/'.$imagen);
        if (! $enUso && is_file($ruta)) {
            unlink($ruta);
        }
    }
}
