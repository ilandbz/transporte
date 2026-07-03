<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouteTariff extends Model
{
    use HasFactory;

    protected $fillable = [
        'route_id',
        'origen_tramo',
        'destino_tramo',
        'ubigeo_origen',
        'ubigeo_destino',
        'precio',
        'clase',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
    ];

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }
}
