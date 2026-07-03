<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GpsTrack extends Model
{
    use HasFactory;

    public $timestamps = false; // Solo usa registrado_en

    protected $fillable = [
        'trip_id',
        'lat',
        'lng',
        'velocidad_kmh',
        'registrado_en',
    ];

    protected $casts = [
        'lat'           => 'decimal:7',
        'lng'           => 'decimal:7',
        'velocidad_kmh' => 'decimal:2',
        'registrado_en' => 'datetime',
    ];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}
