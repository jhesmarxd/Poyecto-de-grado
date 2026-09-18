<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoogleCalendarConnection extends Model
{
    protected $fillable = [
        'connected_by',
        'account_email',
        'calendar_id',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'enabled',
        'connected_at',
        'last_synced_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime',
            'enabled' => 'boolean',
            'connected_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    public function administrator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'connected_by');
    }
}
