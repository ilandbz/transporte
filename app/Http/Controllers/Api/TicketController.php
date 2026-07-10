<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Models\Trip;
use App\Services\TicketService;
use App\Services\SunatGreenterService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

class TicketController extends Controller
{
    public function __construct(
        private TicketService $ticketService,
        private SunatGreenterService $greenter
    ) {}

    // POST /api/v1/tickets — Vender pasaje
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'trip_id'                 => 'required|exists:trips,id',
            'uuid_local'              => 'required|uuid',
            'numero_asiento'          => 'required|integer|min:1',
            'clase'                   => 'required|in:normal,vip',
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

    // GET /api/v1/tickets/{ticket}/pdf — Comprobante descargable para compartir (WhatsApp, etc.)
    public function pdf(Ticket $ticket): Response
    {
        $qrCode = new QrCode($ticket->uuid_local);
        $writer = new PngWriter();
        $qrBase64 = 'data:image/png;base64,' . base64_encode($writer->write($qrCode)->getString());

        $pdf = Pdf::loadView('pdf.ticket', ['ticket' => $ticket, 'qrBase64' => $qrBase64])
            ->setPaper([0, 0, 226.77, 400], 'portrait'); // ~80mm de ancho, alto flexible

        $nombre = 'comprobante-' . ($ticket->serie_cpe ?: $ticket->uuid_local) . '.pdf';

        return $pdf->stream($nombre);
    }

    // PATCH /api/v1/tickets/{ticket}/anular
    public function anular(Request $request, Ticket $ticket): JsonResponse
    {
        $validated = $request->validate([
            'motivo' => 'required|string|min:3|max:100',
        ]);

        if ($ticket->estado === 'anulado') {
            return response()->json(['error' => 'El ticket ya está anulado.'], 400);
        }

        if (!in_array($ticket->tipo_documento, ['BOLETA', 'FACTURA']) || !$ticket->sincronizado || !$ticket->serie_cpe) {
            // Anulación interna (no requiere Greenter baja)
            $ticket->update([
                'estado' => 'anulado',
                'estado_pago' => 'anulado'
            ]);
            $ticket->trip->liberarAsiento($ticket->numero_asiento);
            return response()->json(new TicketResource($ticket));
        }

        // Anulación con SUNAT (Comunicación de baja)
        $res = $this->greenter->anularComprobante($ticket, $validated['motivo']);

        if ($res['status']) {
            $ticket->update([
                'estado' => 'anulado',
                'estado_pago' => 'anulado',
                'cdr_status' => $res['cdr'],
                'cdr_descripcion' => $res['descripcion'],
            ]);
            $ticket->trip->liberarAsiento($ticket->numero_asiento);
            return response()->json(new TicketResource($ticket));
        }

        return response()->json([
            'error' => 'Error al comunicar baja a SUNAT: ' . ($res['error'] ?? $res['descripcion'] ?? 'Error desconocido')
        ], 500);
    }
}
