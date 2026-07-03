<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CpeError extends Model
{
    use HasFactory;

    protected $fillable = [
        'documento_type',
        'documento_id',
        'error_mensaje',
        'payload_enviado',
        'intentos',
        'ultimo_intento',
        'resuelto',
    ];

    protected $casts = [
        'payload_enviado' => 'array',
        'resuelto'        => 'boolean',
        'ultimo_intento'  => 'datetime',
        'intentos'        => 'integer',
    ];

    public function documento(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopePendiente(Builder $query): Builder
    {
        return $query->where('resuelto', false);
    }
}
