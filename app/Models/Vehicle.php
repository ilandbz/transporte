<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'placa',
        'marca',
        'modelo',
        'tipo',
        'capacidad_asientos',
        'layout_asientos',
        'activo',
    ];

    protected $casts = [
        'layout_asientos' => 'array',
        'activo'          => 'boolean',
    ];

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    public function scopeActivo(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function getAsientosDisponibles(Trip $trip): array
    {
        $ocupados = $trip->asientos_ocupados ?? [];
        return array_values(array_diff(range(1, $this->capacidad_asientos), $ocupados));
    }
}
