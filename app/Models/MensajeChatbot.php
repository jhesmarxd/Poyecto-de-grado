<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MensajeChatbot extends Model
{
    use HasFactory;

    /**
     * El pluralizador de Laravel generaría 'mensajes_chatbots';
     * se fija explícitamente el nombre real de la tabla.
     */
    protected $table = 'mensajes_chatbot';

    public const EMISOR_USUARIO = 'usuario';

    public const EMISOR_BOT = 'bot';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'conversacion_id',
        'emisor',
        'contenido',
        'intencion',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'emisor' => 'string',
        ];
    }

    /**
     * Conversación a la que pertenece el mensaje.
     */
    public function conversacion(): BelongsTo
    {
        return $this->belongsTo(Conversacion::class);
    }

    public function ejecucion(): HasOne
    {
        return $this->hasOne(ChatbotEjecucion::class, 'mensaje_chatbot_id');
    }
}
