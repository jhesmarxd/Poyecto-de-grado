<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HorarioSemanal extends Model
{
    protected $table = 'horarios_semanales';

    protected $fillable = ['dia_semana', 'abierto', 'hora_apertura', 'hora_cierre'];

    protected function casts(): array
    {
        return ['dia_semana' => 'integer', 'abierto' => 'boolean'];
    }
}
