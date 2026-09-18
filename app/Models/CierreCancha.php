<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CierreCancha extends Model
{
    protected $table = 'cierres_cancha';

    protected $fillable = ['cancha_id', 'fecha', 'motivo'];

    protected function casts(): array
    {
        return ['fecha' => 'date'];
    }

    public function cancha(): BelongsTo
    {
        return $this->belongsTo(Cancha::class);
    }
}
