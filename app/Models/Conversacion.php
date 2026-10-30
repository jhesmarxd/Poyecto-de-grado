<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversacion extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_EXPIRED = 'expired';

    /**
     * El pluralizador de Laravel generaría 'conversacions';
     * se fija explícitamente el nombre real de la tabla.
     */
    protected $table = 'conversaciones';

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'contexto',
        'status',
        'last_activity_at',
        'context_expires_at',
        'closed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'contexto' => 'array',
            'last_activity_at' => 'datetime',
            'context_expires_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Usuario (opcional) que mantiene la conversación.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Mensajes de la conversación, en orden cronológico.
     */
    public function mensajes(): HasMany
    {
        return $this->hasMany(MensajeChatbot::class);
    }

    public function ejecuciones(): HasMany
    {
        return $this->hasMany(ChatbotEjecucion::class);
    }

    /**
     * Lee un valor del contexto de la conversación.
     */
    public function contextoDe(string $clave, mixed $porDefecto = null): mixed
    {
        return $this->contexto[$clave] ?? $porDefecto;
    }

    /**
     * Actualiza el contexto conservando los valores existentes.
     *
     * @param  array<string, mixed>  $nuevosValores
     */
    public function actualizarContexto(array $nuevosValores): void
    {
        $this->update(['contexto' => array_merge($this->contexto ?? [], $nuevosValores)]);
    }
}
