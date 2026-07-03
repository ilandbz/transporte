<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Route extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'origen',
        'destino',
        'ubigeo_origen',
        'ubigeo_destino',
        'paradas',
        'activo',
    ];

    protected $casts = [
        'paradas' => 'array',
        'activo'  => 'boolean',
    ];

    // Relaciones
    public function tariffs(): HasMany
    {
        return $this->hasMany(RouteTariff::class);
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    // Scopes
    public function scopeActivo(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    // Helper: obtener tarifa para un tramo
    public function getTarifaPara(string $origen, string $destino, string $clase = 'normal'): ?RouteTariff
    {
        return $this->tariffs()
            ->where('origen_tramo', $origen)
            ->where('destino_tramo', $destino)
            ->where('clase', $clase)
            ->first();
    }
}
