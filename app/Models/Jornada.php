<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jornada extends Model
{
    protected $fillable = [
        'user_id',
        'vehicle_id',
        'fecha',
        'estado',
        'lat_inicio',
        'lng_inicio',
        'iniciado_en',
        'cerrado_en',
    ];

    protected $casts = [
        'fecha'       => 'date',
        'iniciado_en' => 'datetime',
        'cerrado_en'  => 'datetime',
    ];

    public function conductor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function gpsTracks(): HasMany
    {
        return $this->hasMany(GpsTrack::class);
    }
}
