<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Models\Trip;
use App\Services\TicketService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TicketController extends Controller
{
    public function __construct(private TicketService $ticketService) {}

    // POST /api/v1/tickets — Vender pasaje
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'trip_id'                 => 'required|exists:trips,id',
            'uuid_local'              => 'required|uuid',
            'numero_asiento'          => 'required|integer|min:1',
            'origen_tramo'            => 'required|string',
            'destino_tramo'           => 'required|string',
            'ubigeo_origen'           => 'required|string|size:6',
            'ubigeo_destino'          => 'required|string|size:6',
            'dni_pasajero'            => 'nullable|string|max:15',
            'nombre_pasajero'         => 'nullable|string|max:200',
            'metodo_pago'             => 'required|in:efectivo,yape,plin,transferencia',
            'tipo_documento'          => 'required|in:BOLETA,FACTURA,TICKET_INTERNO',
            'emitido_en'              => 'required|date',
            'emitido_en_contingencia' => 'required|boolean',
        ]);

        $trip = Trip::findOrFail($validated['trip_id']);

        try {
            $ticket = $this->ticketService->create($validated, $trip);
            return response()->json(new TicketResource($ticket), 201);
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'Asiento')) {
                return response()->json(['error' => $e->getMessage()], 422);
            }
            return response()->json(['error' => 'Error al crear ticket: ' . $e->getMessage()], 500);
        }
    }

    // GET /api/v1/tickets/{ticket}
    public function show(Ticket $ticket): JsonResponse
    {
        return response()->json(new TicketResource($ticket->load(['trip.route', 'trip.vehicle'])));
    }
}
