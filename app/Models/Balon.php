<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Balon extends Model
{
    /**
     * La tabla usa el plural en español ("balones").
     */
    protected $table = 'balones';

    public const TIPO_ECONOMICO = 'economico';
    public const TIPO_PREMIUM = 'premium';

    public const TIPOS = [
        self::TIPO_ECONOMICO => 'Balón económico (se presta)',
        self::TIPO_PREMIUM => 'Balón premium',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tipo',
        'marca',
        'nombre',
        'color',
        'calidad',
        'imagen',
        'precio_por_hora',
        'descripcion',
        'destacado',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'precio_por_hora' => 'decimal:2',
            'tipo' => 'string',
            'destacado' => 'boolean',
        ];
    }

    /**
     * Todos los balones económicos que la dueña presta a los clientes.
     */
    public static function economicos(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('tipo', self::TIPO_ECONOMICO)->orderBy('nombre')->get();
    }

    /**
     * Todos los balones premium (mejor calidad, con recargo).
     */
    public static function premiums(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('tipo', self::TIPO_PREMIUM)->orderBy('nombre')->get();
    }

    /**
     * Balón económico destacado (referencia de la tarifa).
     * Si no hay ninguno marcado, devuelve el primero disponible.
     */
    public static function economico(): ?self
    {
        return static::where('tipo', self::TIPO_ECONOMICO)
            ->orderByDesc('destacado')
            ->orderBy('id')
            ->first();
    }

    /**
     * Balón premium destacado (referencia de la tarifa premium).
     * Si no hay ninguno marcado, devuelve el primero disponible.
     */
    public static function premium(): ?self
    {
        return static::where('tipo', self::TIPO_PREMIUM)
            ->orderByDesc('destacado')
            ->orderBy('id')
            ->first();
    }

    /**
     * Versión de "cambio" del catálogo de balones: el updated_at más reciente.
     * Se usa para que el cliente detecte actualizaciones en tiempo real.
     */
    public static function version(): string
    {
        return (string) (static::query()->max('updated_at') ?? 'v0');
    }

    /**
     * URL pública de la imagen del balón (o null si no tiene).
     */
    public function imagenUrl(?string $version = null): ?string
    {
        if (! $this->imagen) {
            return null;
        }

        return asset('img/balones/'.$this->imagen).($version ? '?v='.$version : '');
    }

    /**
     * Etiqueta legible del tipo.
     */
    public function tipoLabel(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }

    /**
     * Nombre comercial: marca + modelo (p. ej. "Mikasa V200W").
     */
    public function marcaModelo(): string
    {
        return trim($this->marca.' '.$this->nombre);
    }
}