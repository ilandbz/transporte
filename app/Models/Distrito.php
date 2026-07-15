<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Distrito extends Model
{
    protected $fillable = ['provincia_id', 'ubigeo', 'nombre'];

    public function provincia(): BelongsTo
    {
        return $this->belongsTo(Provincia::class);
    }
}
