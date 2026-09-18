<?php

namespace App\Models;

use App\Support\Color;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Ajuste extends Model
{
    public const CACHE_COLORES = 'ajustes.colores';

    public const CACHE_HORARIO_ATENCION = 'ajustes.horario_atencion';

    public const CLAVES_COLORES = ['color_primario', 'color_acento', 'color_oscuro'];

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
     * Devuelve los colores del tema con sus derivados, usando valores
     * por defecto cuando el administrador aún no los ha configurado.
     *
     * @return array<string, string>
     */
    public static function colores(): array
    {
        return Cache::remember(self::CACHE_COLORES, 3600, function () {
            $valores = static::query()
                ->whereIn('clave', self::CLAVES_COLORES)
                ->pluck('valor', 'clave')
                ->all();

            $primario = $valores['color_primario'] ?? '#008C95';
            $acento = $valores['color_acento'] ?? '#B8E000';
            $oscuro = $valores['color_oscuro'] ?? '#071A2B';

            return [
                'color_primario' => $primario,
                'color_primario_oscuro' => Color::mezclar($primario, -0.18),
                'color_primario_rgb' => Color::rgbCss($primario),
                'color_acento' => $acento,
                'color_acento_oscuro' => Color::mezclar($acento, -0.15),
                'color_acento_rgb' => Color::rgbCss($acento),
                'color_oscuro' => $oscuro,
                'color_oscuro_rgb' => Color::rgbCss($oscuro),
            ];
        });
    }

    /**
     * Colores seguros cuando la base de datos todavia no esta disponible.
     *
     * @return array<string, string>
     */
    public static function coloresPorDefecto(): array
    {
        $primario = '#008C95';
        $acento = '#B8E000';
        $oscuro = '#071A2B';

        return [
            'color_primario' => $primario,
            'color_primario_oscuro' => Color::mezclar($primario, -0.18),
            'color_primario_rgb' => Color::rgbCss($primario),
            'color_acento' => $acento,
            'color_acento_oscuro' => Color::mezclar($acento, -0.15),
            'color_acento_rgb' => Color::rgbCss($acento),
            'color_oscuro' => $oscuro,
            'color_oscuro_rgb' => Color::rgbCss($oscuro),
        ];
    }

    /**
     * Guarda los colores configurados e invalida la caché.
     *
     * @param  array<string, string>  $colores
     */
    public static function guardarColores(array $colores): void
    {
        foreach ($colores as $clave => $valor) {
            static::updateOrCreate(['clave' => $clave], ['valor' => $valor]);
        }

        Cache::forget(self::CACHE_COLORES);
    }

    /**
     * Devuelve el horario informativo general sin mezclarlo con las franjas
     * que se utilizan para comprobar disponibilidad y crear reservas.
     *
     * @return array{dia_inicio: int, dia_fin: int, dia_inicio_nombre: string, dia_fin_nombre: string, hora_inicio: string, hora_fin: string}|null
     */
    public static function horarioAtencion(): ?array
    {
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
