<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotEjecucion extends Model
{
    use HasFactory;

    public const MOTOR_EXTERNAL = 'external';

    public const MOTOR_LOCAL = 'local';

    public const MOTOR_DRAFT = 'draft';

    public const MOTOR_BLOCKED = 'blocked';

    public const MOTOR_NONE = 'none';

    protected $table = 'chatbot_ejecuciones';

    protected $fillable = [
        'mensaje_chatbot_id',
        'conversacion_id',
        'provider',
        'model',
        'external_status',
        'external_attempted',
        'external_used',
        'fallback_local',
        'engine_used',
        'external_latency_ms',
        'external_http_status',
        'external_error_code',
        'external_error_message',
        'detected_intent',
        'structured_parameters',
    ];

    protected function casts(): array
    {
        return [
            'external_attempted' => 'boolean',
            'external_used' => 'boolean',
            'fallback_local' => 'boolean',
            'external_latency_ms' => 'integer',
            'external_http_status' => 'integer',
            'structured_parameters' => 'array',
        ];
    }

    public function mensaje(): BelongsTo
    {
        return $this->belongsTo(MensajeChatbot::class, 'mensaje_chatbot_id');
    }

    public function conversacion(): BelongsTo
    {
        return $this->belongsTo(Conversacion::class);
    }
}
