<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DniCache extends Model
{
    use HasFactory;

    protected $table = 'dni_cache';

    protected $fillable = [
        'tipo',
        'numero',
        'nombre',
        'extra_json',
        'consultado_en',
    ];

    protected $casts = [
        'extra_json'    => 'array',
        'consultado_en' => 'datetime',
    ];
}
