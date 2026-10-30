<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoogleCalendarEvent extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SYNCED = 'synced';

    public const STATUS_ERROR = 'error';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_DELETED = 'deleted';

    protected $fillable = [
        'reserva_id',
        'calendar_id',
        'google_event_id',
        'google_event_url',
        'etag',
        'sync_status',
        'payload_hash',
        'last_error',
        'synced_at',
    ];

    protected function casts(): array
    {
        return ['synced_at' => 'datetime'];
    }

    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class);
    }

    /**
     * Enlace externo válido únicamente para un evento de Google Calendar.
     */
    public function urlSegura(): ?string
    {
        if (! $this->google_event_url || filter_var($this->google_event_url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        $host = strtolower((string) parse_url($this->google_event_url, PHP_URL_HOST));
        $scheme = strtolower((string) parse_url($this->google_event_url, PHP_URL_SCHEME));

        return $scheme === 'https' && in_array($host, ['calendar.google.com', 'www.google.com'], true)
            ? $this->google_event_url
            : null;
    }
}
