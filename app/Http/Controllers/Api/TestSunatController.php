<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Services\SunatGreenterService;
use Illuminate\Http\JsonResponse;

class TestSunatController extends Controller
{
    public function __construct(private SunatGreenterService $sunat) {}

    // GET /api/v1/test/sunat/conexion
    public function conexion(): JsonResponse
    {
        $resultado = $this->sunat->testConexion();
        return response()->json($resultado);
    }

    // POST /api/v1/test/sunat/boleta/{ticket}
    public function boleta(Ticket $ticket): JsonResponse
    {
        $resultado = $this->sunat->emitirBoleta($ticket);
        return response()->json($resultado);
    }
}
