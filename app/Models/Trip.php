<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'route_id',
        'vehicle_id',
        'user_id',
        'placa_vehiculo',
        'numero_manifiesto',
        'fecha_salida',
        'fecha_llegada_estimada',
        'estado',
        'asientos_ocupados',
        'lat_inicio',
        'lng_inicio',
    ];

    protected $casts = [
        'asientos_ocupados'      => 'array',
        'fecha_salida'           => 'datetime',
        'fecha_llegada_estimada' => 'datetime',
    ];

    // Relaciones
    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function conductor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
    }

    public function gpsTracks(): HasMany
    {
        return $this->hasMany(GpsTrack::class);
    }

    // Scopes
    public function scopeAbierto(Builder $query): Builder
    {
        return $query->where('estado', 'abierto');
    }

    public function scopeEnRuta(Builder $query): Builder
    {
        return $query->where('estado', 'en_ruta');
    }

    public function scopeHoy(Builder $query): Builder
    {
        return $query->whereDate('fecha_salida', today());
    }

    // Helpers de asientos
    public function isAsientoOcupado(int $numero): bool
    {
        return in_array($numero, $this->asientos_ocupados ?? []);
    }

    public function ocuparAsiento(int $numero): void
    {
        $ocupados = $this->asientos_ocupados ?? [];
        if (!in_array($numero, $ocupados)) {
            $ocupados[] = $numero;
            $this->update(['asientos_ocupados' => $ocupados]);
        }
    }

    public function liberarAsiento(int $numero): void
    {
        $ocupados = array_values(array_filter(
            $this->asientos_ocupados ?? [],
            fn($n) => $n !== $numero
        ));
        $this->update(['asientos_ocupados' => $ocupados]);
    }
}
