<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lugar;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LugarController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Lugar::where('activo', true)->orderBy('nombre')->get(['id', 'nombre'])
        );
    }
}
