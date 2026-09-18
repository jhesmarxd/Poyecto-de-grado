<?php

namespace App\Support;

class Color
{
    /**
     * Devuelve los componentes RGB de un color hex (#RRGGBB).
     *
     * @return array{0: int, 1: int, 2: int}
     */
    public static function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * Cadena "r, g, b" lista para usar en variables CSS (--bs-*-rgb).
     */
    public static function rgbCss(string $hex): string
    {
        return implode(', ', self::rgb($hex));
    }

    /**
     * Oscurece o aclara un color hex. Factor negativo oscurece, positivo aclara.
     */
    public static function mezclar(string $hex, float $factor): string
    {
        [$r, $g, $b] = self::rgb($hex);

        if ($factor >= 0) {
            $r = (int) round($r + (255 - $r) * $factor);
            $g = (int) round($g + (255 - $g) * $factor);
            $b = (int) round($b + (255 - $b) * $factor);
        } else {
            $f = 1 + $factor;
            $r = (int) round($r * $f);
            $g = (int) round($g * $f);
            $b = (int) round($b * $f);
        }

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
}
