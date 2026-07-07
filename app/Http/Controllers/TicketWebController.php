<?php
namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TicketWebController extends Controller
{
    public function __construct(private \App\Services\TicketService $ticketService) {}

    public function index(Request $request): Response
    {
        $query = Ticket::with(['trip.vehicle', 'trip.route', 'vendedor'])
            ->orderByDesc('emitido_en');

        if ($request->filled('fecha')) {
            $query->whereDate('emitido_en', $request->fecha);
        }
        if ($request->filled('tipo_documento')) {
            $query->where('tipo_documento', $request->tipo_documento);
        }
        if ($request->filled('sincronizado') && $request->sincronizado !== '') {
            $query->where('sincronizado', filter_var($request->sincronizado, FILTER_VALIDATE_BOOLEAN));
        }
        if ($request->filled('placa')) {
            $query->where('placa_vehiculo', $request->placa);
        }

        $tickets = $query->paginate(30)->withQueryString();
        $filtros  = $request->only(['fecha','tipo_documento','sincronizado','placa']);

        $stats = [
            'total_hoy'       => Ticket::whereDate('emitido_en', today())->count(),
            'sin_sincronizar' => Ticket::where('sincronizado', false)->count(),
            'aceptados_hoy'   => Ticket::whereDate('emitido_en', today())->where('cdr_status','0')->count(),
        ];

        return Inertia::render('Tickets/Index', compact('tickets','filtros','stats'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'trip_id'                 => 'required|exists:trips,id',
            'uuid_local'              => 'required|uuid',
            'numero_asiento'          => 'required|integer|min:1',
            'clase'                   => 'required|string|in:normal,vip',
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

        $trip = \App\Models\Trip::findOrFail($validated['trip_id']);

        try {
            $ticket = $this->ticketService->create($validated, $trip);
            return back()->with('success', 'Ticket creado correctamente.');
        } catch (\Exception $e) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'error' => $e->getMessage()
            ]);
        }
    }

    public function destroy(Ticket $ticket)
    {
        if ($ticket->trip && $ticket->trip->estado !== 'abierto') {
            return back()->with('error', 'No se puede eliminar un ticket de un viaje en curso o finalizado.');
        }

        if ($ticket->trip && $ticket->numero_asiento) {
            $ticket->trip->liberarAsiento($ticket->numero_asiento);
        }

        $ticket->delete();

        return back()->with('success', 'Ticket eliminado correctamente.');
    }
}
