<?php

namespace App\Services\Chatbot;

use App\Models\Cancha;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ExtractorEntidades
{
    /**
     * Palabras demasiado genéricas que no sirven para identificar una cancha
     * (evitan que "cancha 2" coincida con "Cancha 1" por "cancha").
     */
    protected const PALABRAS_GENERICAS = [
        'cancha', 'las', 'los', 'la', 'el', 'de', 'del', 'una', 'un', 'para', 'en',
    ];

    /**
     * Detecta la cancha mencionada en el mensaje usando los nombres
     * reales registrados en la base de datos.
     */
    public function cancha(string $mensaje): ?Cancha
    {
        $texto = $this->normalizar($mensaje);

        if ($texto === '') {
            return null;
        }

        // Prefiere coincidencias más largas (evita que "1" de "Cancha 1"
        // se cruce con otra cancha). Se ordena por longitud de nombre desc
        // en PHP para que funcione igual en MySQL y SQLite (tests).
        $canchas = Cancha::query()->get()->sortByDesc(fn (Cancha $c) => mb_strlen($c->nombre));

        foreach ($canchas as $cancha) {
            $nombre = $this->normalizar($cancha->nombre);

            if ($nombre === '') {
                continue;
            }

            // Coincidencia por nombre completo: "cancha 1"
            if (Str::contains($texto, $nombre)) {
                return $cancha;
            }

            // Coincidencia por palabra significativa: "la 1" / "la 2" / "cancha 1"
            // Se ignora "cancha" y otras palabras genéricas para evitar falsos positivos.
            foreach (preg_split('/\s+/', $nombre) ?: [] as $palabra) {
                if (in_array($palabra, self::PALABRAS_GENERICAS, true)) {
                    continue;
                }
                // Los números ("1", "2") son relevantes aunque tengan 1 carácter.
                if ((mb_strlen($palabra) >= 3 || ctype_digit($palabra)) && Str::contains($texto, $palabra)) {
                    return $cancha;
                }
            }
        }

        return null;
    }

    /**
     * Detecta una fecha mencionada en el mensaje.
     * Soporta: hoy, mañana, pasado mañana, días de la semana,
     * "día de mes" (15/08, 15 de agosto), y fechas ISO.
     */
    public function fecha(string $mensaje): ?string
    {
        $texto = Str::lower(Str::ascii($mensaje));

        if (Str::contains($texto, 'pasado manana')) {
            return now()->addDays(2)->toDateString();
        }
        if (Str::contains($texto, 'manana')) {
            return now()->addDay()->toDateString();
        }
        if (Str::contains($texto, 'hoy') || Str::contains($texto, 'esta noche')) {
            return now()->toDateString();
        }

        // Días de la semana: "el lunes", "el sábado"
        $dias = [
            'lunes' => 1, 'martes' => 2, 'miercoles' => 3, 'jueves' => 4,
            'viernes' => 5, 'sabado' => 6, 'domingo' => 7,
        ];
        foreach ($dias as $nombre => $dia) {
            if (Str::contains($texto, $nombre)) {
                return $this->proximoDia($dia)->toDateString();
            }
        }

        // Fechas explícitas: 15/08, 15-08-2026, 2026-08-15, 15 de agosto
        if (preg_match('/(\d{1,2})[\/\-](\d{1,2})(?:[\/\-](\d{2,4}))?/', $texto, $m)) {
            $anio = isset($m[3]) && strlen($m[3]) === 4 ? (int) $m[3] : now()->year;
            $fecha = Carbon::create($anio, (int) $m[2], (int) $m[1]);
            if ($fecha && $fecha->year > 1900) {
                return $fecha->toDateString();
            }
        }

        $meses = [
            'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4, 'mayo' => 5,
            'junio' => 6, 'julio' => 7, 'agosto' => 8, 'septiembre' => 9,
            'octubre' => 10, 'noviembre' => 11, 'diciembre' => 12,
        ];
        foreach ($meses as $mes => $numero) {
            if (preg_match('/(\d{1,2})\s+de\s+'.$mes.'/', $texto, $m)) {
                return Carbon::create(now()->year, $numero, (int) $m[1])->toDateString();
            }
        }

        return null;
    }

    /**
     * Detecta una hora y la normaliza como HH:MM:SS.
     */
    public function hora(string $mensaje): ?string
    {
        $texto = Str::lower(Str::ascii($mensaje));

        $texto = preg_replace('/\b([ap])\s*\.?\s*m\.?\b/', '$1m', $texto) ?? $texto;
        $patronSufijo = 'am|pm|de la manana|de la tarde|de la noche';

        if (preg_match('/(?<!\d)(\d{1,2}):(\d{2})\s*('.$patronSufijo.')?(?!\d)/', $texto, $m)) {
            return $this->normalizarHora((int) $m[1], (int) $m[2], $m[3] ?? '');
        }

        // "a las 6", "a las 18", "a la 1", "6 pm", "6 am"
        if (preg_match('/(?:(?:a\s+las?|las?)\s+)?(?<!\d)(\d{1,2})\s*('.$patronSufijo.')(?!\w)/', $texto, $m)) {
            return $this->normalizarHora((int) $m[1], 0, $m[2]);
        }

        if (preg_match('/(?:a\s+las?|las?)\s+(\d{1,2})(?![\d:])/', $texto, $m)) {
            return $this->normalizarHora((int) $m[1], 0);
        }

        return null;
    }

    /** @return array{inicio: string, fin: string}|null */
    public function rangoHoras(string $mensaje): ?array
    {
        $texto = Str::lower(Str::ascii($mensaje));
        $texto = preg_replace('/\b([ap])\s*\.?\s*m\.?\b/', '$1m', $texto) ?? $texto;
        $sufijo = 'am|pm|de la manana|de la tarde|de la noche';

        if (! preg_match(
            '/\b(?:de|desde)\s+(\d{1,2})(?::(\d{2}))?\s*('.$sufijo.')?\s+(?:a|hasta)\s+(?:las?\s+)?(\d{1,2})(?::(\d{2}))?\s*('.$sufijo.')?\b/',
            $texto,
            $partes
        )) {
            return null;
        }

        $sufijoInicio = $partes[3] ?: ($partes[6] ?? '');
        $inicio = $this->normalizarHora((int) $partes[1], (int) ($partes[2] ?: 0), $sufijoInicio);
        $fin = $this->normalizarHora((int) $partes[4], (int) ($partes[5] ?: 0), $partes[6] ?? '');

        return $inicio && $fin && $fin > $inicio
            ? ['inicio' => $inicio, 'fin' => $fin]
            : null;
    }

    public function duracionMinutos(string $mensaje): ?int
    {
        $texto = Str::lower(Str::ascii($mensaje));

        if (preg_match('/\b(?:por|durante)\s+(\d{1,2})\s*(?:horas?|hrs?)\b/', $texto, $partes)) {
            $minutos = (int) $partes[1] * 60;

            return $minutos <= 720 ? $minutos : null;
        }

        if (preg_match('/\b(?:por|durante)\s+(\d{2,3})\s*minutos?\b/', $texto, $partes)) {
            $minutos = (int) $partes[1];

            return $minutos >= 15 && $minutos <= 720 ? $minutos : null;
        }

        return null;
    }

    public function reservaId(string $mensaje): ?int
    {
        return preg_match('/\breserva\s*#?\s*(\d+)\b/i', $mensaje, $partes)
            ? max(1, (int) $partes[1])
            : null;
    }

    /**
     * Convierte formatos de 12 y 24 horas al valor guardado en la base de datos.
     */
    private function normalizarHora(int $hora, int $minuto, string $sufijo = ''): ?string
    {
        if ($minuto < 0 || $minuto > 59 || $hora < 0 || $hora > 23) {
            return null;
        }

        $esAm = Str::contains($sufijo, ['am', 'manana']);
        $esPm = Str::contains($sufijo, ['pm', 'tarde', 'noche']);

        if (($esAm || $esPm) && $hora <= 12) {
            if ($esAm && $hora === 12) {
                $hora = 0;
            } elseif ($esPm && $hora < 12) {
                $hora += 12;
            }
        } elseif (! $esAm && ! $esPm && $hora <= 7) {
            $hora += 12;
        }

        return sprintf('%02d:%02d:00', $hora, $minuto);
    }

    /**
     * Calcula la próxima fecha que coincide con el día de la semana indicado.
     */
    private function proximoDia(int $dia): Carbon
    {
        $hoy = now()->startOfDay();
        $diasHasta = ($dia - $hoy->dayOfWeekIso + 7) % 7;

        return $hoy->copy()->addDays($diasHasta);
    }

    /**
     * Normaliza texto para comparaciones: minúsculas, sin tildes ni puntuación.
     */
    private function normalizar(string $texto): string
    {
        $texto = Str::lower(Str::ascii($texto));

        return preg_replace('/[^a-z0-9\s]/u', ' ', $texto) ?? '';
    }
}
