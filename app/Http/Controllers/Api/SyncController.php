<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SyncService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SyncController extends Controller
{
    public function __construct(private SyncService $syncService) {}

    // POST /api/v1/sync/batch — Lote offline
    public function batch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'trip_id'                           => 'required|exists:trips,id',
            'tickets'                           => 'required|array|min:1|max:100',
            'tickets.*.uuid_local'              => 'required|uuid',
            'tickets.*.numero_asiento'          => 'required|integer',
            'tickets.*.origen_tramo'            => 'required|string',
            'tickets.*.destino_tramo'           => 'required|string',
            'tickets.*.ubigeo_origen'           => 'required|string|size:6',
            'tickets.*.ubigeo_destino'          => 'required|string|size:6',
            'tickets.*.precio'                  => 'required|numeric',
            'tickets.*.metodo_pago'             => 'required|in:efectivo,yape,plin,transferencia',
            'tickets.*.emitido_en'              => 'required|date',
            'tickets.*.emitido_en_contingencia' => 'required|boolean',
        ]);

        $resultado = $this->syncService->processBatch(
            $validated['tickets'],
            auth()->user()
        );

        return response()->json($resultado);
    }
}
