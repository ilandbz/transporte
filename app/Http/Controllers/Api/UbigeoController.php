<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Departamento;
use App\Models\Provincia;
use Illuminate\Http\JsonResponse;

class UbigeoController extends Controller
{
    // GET /api/v1/ubigeo/departamentos
    public function departamentos(): JsonResponse
    {
        return response()->json(
            Departamento::orderBy('nombre')->get(['id', 'codigo', 'nombre'])
        );
    }

    // GET /api/v1/ubigeo/departamentos/{departamento}/provincias
    public function provincias(Departamento $departamento): JsonResponse
    {
        return response()->json(
            $departamento->provincias()->orderBy('nombre')->get(['id', 'codigo', 'nombre'])
        );
    }

    // GET /api/v1/ubigeo/provincias/{provincia}/distritos
    public function distritos(Provincia $provincia): JsonResponse
    {
        return response()->json(
            $provincia->distritos()->orderBy('nombre')->get(['id', 'ubigeo', 'nombre'])
        );
    }
}
