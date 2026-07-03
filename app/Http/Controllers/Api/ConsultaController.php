<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DniRucApiService;
use Illuminate\Http\JsonResponse;

class ConsultaController extends Controller
{
    public function __construct(private DniRucApiService $dniService) {}

    // GET /api/v1/consulta/dni/{dni}
    public function dni(string $dni): JsonResponse
    {
        $result = $this->dniService->consultarDni($dni);

        if (!$result) {
            return response()->json(['encontrado' => false], 404);
        }

        return response()->json(['encontrado' => true, 'datos' => $result]);
    }

    // GET /api/v1/consulta/ruc/{ruc}
    public function ruc(string $ruc): JsonResponse
    {
        $result = $this->dniService->consultarRuc($ruc);

        if (!$result) {
            return response()->json(['encontrado' => false], 404);
        }

        return response()->json(['encontrado' => true, 'datos' => $result]);
    }
}
