<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid_local',
        'trip_id',
        'user_id',
        'remitente_nombre',
        'remitente_dni',
        'remitente_telefono',
        'destinatario_nombre',
        'destinatario_dni',
        'destinatario_telefono',
        'descripcion',
        'peso_kg',
        'cantidad_bultos',
        'precio',
        'qr_code',
        'estado',
        'estado_pago',
        'entregado_en',
        'tipo_documento',
        'sincronizado',
        'emitido_en_contingencia',
        'emitido_en',
        'sincronizado_en',
    ];

    protected $casts = [
        'precio'                  => 'decimal:2',
        'peso_kg'                 => 'decimal:2',
        'sincronizado'            => 'boolean',
        'emitido_en_contingencia' => 'boolean',
        'emitido_en'              => 'datetime',
        'sincronizado_en'         => 'datetime',
        'entregado_en'            => 'datetime',
    ];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopePendienteSync(Builder $query): Builder
    {
        return $query->where('sincronizado', false);
    }

    public function scopePendienteEntrega(Builder $query): Builder
    {
        return $query->where('estado', 'en_transito');
    }
}
