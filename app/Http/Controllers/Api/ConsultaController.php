<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Services\DniRucApiService;
use Illuminate\Http\JsonResponse;

class ConsultaController extends Controller
{
    public function __construct(private DniRucApiService $dniService) {}

    // GET /api/v1/consulta/dni/{dni}
    public function dni(string $dni): JsonResponse
    {
        $cliente = Client::where('documento', $dni)->first();
        if ($cliente) {
            return response()->json([
                'encontrado' => true,
                'origen'     => 'clientes',
                'datos'      => [
                    'numero'   => $cliente->documento,
                    'nombre'   => $cliente->nombre,
                    'telefono' => $cliente->telefono,
                    'email'    => $cliente->email,
                ],
            ]);
        }

        $result = $this->dniService->consultarDni($dni);

        if (!$result) {
            return response()->json(['encontrado' => false], 404);
        }

        return response()->json(['encontrado' => true, 'origen' => 'reniec', 'datos' => $result]);
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
