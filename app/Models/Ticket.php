<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid_local',
        'trip_id',
        'user_id',
        'numero_asiento',
        'clase',
        'origen_tramo',
        'destino_tramo',
        'ubigeo_origen',
        'ubigeo_destino',
        'dni_pasajero',
        'nombre_pasajero',
        'placa_vehiculo',
        'precio',
        'estado_pago',
        'estado',
        'metodo_pago',
        'tipo_documento',
        'documento_facturacion',
        'nombre_facturacion',
        'serie_cpe',
        'correlativo_cpe',
        'hash_cpe',
        'cdr_status',
        'cdr_descripcion',
        'sincronizado',
        'emitido_en_contingencia',
        'emitido_en',
        'sincronizado_en',
    ];

    protected $casts = [
        'precio'                  => 'decimal:2',
        'sincronizado'            => 'boolean',
        'emitido_en_contingencia' => 'boolean',
        'emitido_en'              => 'datetime',
        'sincronizado_en'         => 'datetime',
        'correlativo_cpe'         => 'integer',
    ];

    // Relaciones
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Scopes
    public function scopePendienteSync(Builder $query): Builder
    {
        return $query->where('sincronizado', false);
    }

    public function scopeContingencia(Builder $query): Builder
    {
        return $query->where('emitido_en_contingencia', true);
    }

    public function scopeAceptado(Builder $query): Builder
    {
        return $query->where('cdr_status', '0');
    }

    public function scopeRechazado(Builder $query): Builder
    {
        return $query->whereNotNull('cdr_status')->where('cdr_status', '!=', '0');
    }

    // Accessors
    public function getNumeroCompletoAttribute(): string
    {
        if ($this->tipo_documento === 'TICKET_INTERNO' || !$this->serie_cpe) {
            return 'TICKET';
        }
        return $this->serie_cpe . '-' . str_pad($this->correlativo_cpe, 8, '0', STR_PAD_LEFT);
    }

    public function getEstaEmitidoAttribute(): bool
    {
        return $this->tipo_documento !== 'TICKET_INTERNO' && $this->cdr_status === '0';
    }
}
