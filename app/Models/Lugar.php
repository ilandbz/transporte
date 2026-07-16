<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lugar extends Model
{
    protected $fillable = ['nombre', 'activo'];

    protected $casts = [
        'activo' => 'boolean',
    ];
}
