<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class Ajuste extends Model
{
    public const CACHE_HORARIO_ATENCION = 'ajustes.horario_atencion';

    public const CLAVES_HORARIO_ATENCION = [
        'horario_atencion_dia_inicio',
        'horario_atencion_dia_fin',
        'horario_atencion_hora_inicio',
        'horario_atencion_hora_fin',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = ['clave', 'valor'];

    /**
     * La tabla de ajustes no tiene marcas de tiempo.
     */
    public $timestamps = false;

    /**
     * Devuelve el horario informativo general sin mezclarlo con las franjas
     * que se utilizan para comprobar disponibilidad y crear reservas.
     *
     * @return array{dia_inicio: int, dia_fin: int, dia_inicio_nombre: string, dia_fin_nombre: string, hora_inicio: string, hora_fin: string}|null
     */
    public static function horarioAtencion(): ?array
    {
        if (Schema::hasTable('horarios_semanales')) {
            $abiertos = HorarioSemanal::query()->where('abierto', true)->orderBy('dia_semana')->get();
            if ($abiertos->isNotEmpty()) {
                $primero = $abiertos->first();
                $ultimo = $abiertos->last();

                return [
                    'dia_inicio' => $primero->dia_semana,
                    'dia_fin' => $ultimo->dia_semana,
                    'dia_inicio_nombre' => Horario::DIAS[$primero->dia_semana],
                    'dia_fin_nombre' => Horario::DIAS[$ultimo->dia_semana],
                    'hora_inicio' => substr((string) $abiertos->min('hora_apertura'), 0, 5),
                    'hora_fin' => substr((string) $abiertos->max('hora_cierre'), 0, 5),
                ];
            }
        }

        return Cache::remember(self::CACHE_HORARIO_ATENCION, 3600, function () {
            $valores = static::query()
                ->whereIn('clave', self::CLAVES_HORARIO_ATENCION)
                ->pluck('valor', 'clave');

            foreach (self::CLAVES_HORARIO_ATENCION as $clave) {
                if (! $valores->has($clave) || blank($valores->get($clave))) {
                    return null;
                }
            }

            $diaInicio = (int) $valores->get('horario_atencion_dia_inicio');
            $diaFin = (int) $valores->get('horario_atencion_dia_fin');

            if (! isset(Horario::DIAS[$diaInicio], Horario::DIAS[$diaFin])) {
                return null;
            }

            return [
                'dia_inicio' => $diaInicio,
                'dia_fin' => $diaFin,
                'dia_inicio_nombre' => Horario::DIAS[$diaInicio],
                'dia_fin_nombre' => Horario::DIAS[$diaFin],
                'hora_inicio' => substr((string) $valores->get('horario_atencion_hora_inicio'), 0, 5),
                'hora_fin' => substr((string) $valores->get('horario_atencion_hora_fin'), 0, 5),
            ];
        });
    }

    /**
     * @param  array<string, int|string>  $horario
     */
    public static function guardarHorarioAtencion(array $horario): void
    {
        foreach ($horario as $clave => $valor) {
            static::updateOrCreate(['clave' => $clave], ['valor' => (string) $valor]);
        }

        Cache::forget(self::CACHE_HORARIO_ATENCION);
    }
}
