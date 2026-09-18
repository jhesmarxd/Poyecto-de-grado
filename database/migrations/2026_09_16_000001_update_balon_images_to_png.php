<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Actualiza el catálogo existente para usar las nuevas imágenes PNG.
     */
    public function up(): void
    {
        foreach ($this->imagenes() as $imagen) {
            DB::table('balones')
                ->where('marca', $imagen['marca'])
                ->where('nombre', $imagen['nombre'])
                ->where('imagen', $imagen['anterior'])
                ->update(['imagen' => $imagen['nueva']]);
        }
    }

    /**
     * Restaura las referencias anteriores si se revierte la migración.
     */
    public function down(): void
    {
        foreach ($this->imagenes() as $imagen) {
            DB::table('balones')
                ->where('marca', $imagen['marca'])
                ->where('nombre', $imagen['nombre'])
                ->where('imagen', $imagen['nueva'])
                ->update(['imagen' => $imagen['anterior']]);
        }
    }

    /**
     * @return array<int, array{marca: string, nombre: string, anterior: string, nueva: string}>
     */
    private function imagenes(): array
    {
        return [
            ['marca' => 'Asatex', 'nombre' => '1', 'anterior' => 'Balon economico Asatex 1.jpeg', 'nueva' => 'Balon economico Asatex 1.png'],
            ['marca' => 'Asatex', 'nombre' => '2', 'anterior' => 'Balon economico Asatex 2.jpeg', 'nueva' => 'Balon economico Asatex 2.png'],
            ['marca' => 'Veloz', 'nombre' => '1', 'anterior' => 'Balon economico Veloz 1.jpeg', 'nueva' => 'Balon economico Veloz 1.png'],
            ['marca' => 'Veloz', 'nombre' => '2', 'anterior' => 'Balon economico Veloz 2.jpeg', 'nueva' => 'Balon economico Veloz 2.png'],
            ['marca' => 'Mikasa', 'nombre' => 'V200W', 'anterior' => 'Balon premiun Mikasa V200W Original.jpeg', 'nueva' => 'Balon premiun Mikasa V200W Original.png'],
            ['marca' => 'Mikasa', 'nombre' => 'V330W', 'anterior' => 'Balon Premiun Mikasa V330W Orginal.jpeg', 'nueva' => 'Balon premiun Mikasa V330W Orginal.png'],
            ['marca' => 'Mikasa', 'nombre' => 'V330W 2', 'anterior' => 'Balon Premiun Mikasa V330W Orginal 2.jpeg', 'nueva' => 'Balon premiun Mikasa V330W Orginal.png'],
            ['marca' => 'Molten', 'nombre' => '5500', 'anterior' => 'Balon Premiun Molten 5500 Original.jpeg', 'nueva' => 'Balon Premiun Molten 5500 Original.png'],
        ];
    }
};
