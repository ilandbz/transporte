<?php
namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\TicketService;
use App\Services\SunatGreenterService;

class TicketWebController extends Controller
{
    protected $ticketService;
    protected $greenter;

    public function __construct(TicketService $ticketService, SunatGreenterService $greenter)
    {
        $this->ticketService = $ticketService;
        $this->greenter = $greenter;
    }

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
            'telefono_pasajero'       => 'required|string|max:20',
            'estado_pago'             => 'required|in:pagado,pendiente',
            'estado'                  => 'required|in:confirmado,reservado',
            'metodo_pago'             => 'required|in:efectivo,yape,plin,transferencia',
            'tipo_documento'          => 'required|in:BOLETA,FACTURA,TICKET_INTERNO',
            'documento_facturacion'   => 'required_if:tipo_documento,FACTURA|nullable|string|max:15',
            'nombre_facturacion'      => 'required_if:tipo_documento,FACTURA|nullable|string|max:255',
            'emitido_en'              => 'required|date',
            'emitido_en_contingencia' => 'required|boolean',
        ]);

        $trip = \App\Models\Trip::findOrFail($validated['trip_id']);

        // Guardar o actualizar en la base de clientes frecuentes
        // Create or update client if passenger DNI exists
        if (!empty($validated['dni_pasajero'])) {
            Client::updateOrCreate(
                ['documento' => $validated['dni_pasajero']],
                [
                    'nombre' => $validated['nombre_pasajero'] ?? 'Pasajero sin nombre',
                    'telefono' => $validated['telefono_pasajero'],
                ]
            );
        }

        // Si es boleta y no se proporcionó datos de facturación explícitos, usar los del pasajero
        if ($validated['tipo_documento'] === 'BOLETA' && empty($validated['documento_facturacion'])) {
            $validated['documento_facturacion'] = $validated['dni_pasajero'];
            $validated['nombre_facturacion'] = $validated['nombre_pasajero'];
        }

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

    public function print(Request $request, Ticket $ticket)
    {
        $ticket->load(['trip.vehicle', 'trip.route', 'vendedor']);
        
        $format = $request->query('format', '80mm'); // '80mm' or 'a4'

        return view('print.ticket', compact('ticket', 'format'));
    }

    public function togglePayment(Ticket $ticket)
    {
        $ticket->estado_pago = $ticket->estado_pago === 'pagado' ? 'pendiente' : 'pagado';
        $ticket->save();

        return back()->with('success', 'Estado de pago actualizado.');
    }

    public function convertCpe(Request $request, Ticket $ticket)
    {
        if ($ticket->tipo_documento !== 'TICKET_INTERNO') {
            return back()->with('error', 'El ticket ya fue convertido.');
        }

        $validated = $request->validate([
            'tipo_documento' => 'required|in:BOLETA,FACTURA',
            'documento_facturacion' => 'required|string|max:15',
            'nombre_facturacion' => 'required|string|max:255',
            'telefono_pasajero' => 'nullable|string|max:20',
        ]);

        $ticket->tipo_documento = $validated['tipo_documento'];
        $ticket->documento_facturacion = $validated['documento_facturacion'];
        $ticket->nombre_facturacion = $validated['nombre_facturacion'];
        $ticket->estado_pago = 'pagado';
        
        // Save client info too
        Client::updateOrCreate(
            ['documento' => $ticket->documento_facturacion],
            [
                'nombre' => $ticket->nombre_facturacion,
                'telefono' => $validated['telefono_pasajero'] ?? null
            ]
        );

        $ticket->save();

        // Send to greenter
        try {
            if ($ticket->tipo_documento === 'BOLETA') {
                $res = $this->greenter->emitirBoleta($ticket);
            } else {
                $res = $this->greenter->emitirFactura($ticket);
            }

            if ($res['status']) {
                $ticket->update([
                    'serie_cpe'       => $res['serie'],
                    'correlativo_cpe' => $res['correlativo'],
                    'cdr_status'      => $res['cdr'],
                    'sincronizado'    => true,
                    'sincronizado_en' => now(),
                ]);
                return back()->with('success', 'Convertido a CPE y emitido correctamente.');
            }

            return back()->with('error', 'Convertido pero no emitido: ' . $res['error']);
        } catch (\Exception $e) {
            return back()->with('error', 'CPE Error: ' . $e->getMessage());
        }
    }

    public function confirmReservation(Request $request, Ticket $ticket)
    {
        if ($ticket->estado !== 'reservado') {
            return back()->with('error', 'Este ticket no es una reservación.');
        }

        $validated = $request->validate([
            'dni_pasajero'            => 'nullable|string|max:15',
            'nombre_pasajero'         => 'nullable|string|max:200',
            'telefono_pasajero'       => 'required|string|max:20',
            'estado_pago'             => 'required|in:pagado,pendiente',
            'estado'                  => 'required|in:confirmado',
            'metodo_pago'             => 'required|in:efectivo,yape,plin,transferencia',
            'tipo_documento'          => 'required|in:BOLETA,FACTURA,TICKET_INTERNO',
            'documento_facturacion'   => 'required_if:tipo_documento,FACTURA|nullable|string|max:15',
            'nombre_facturacion'      => 'required_if:tipo_documento,FACTURA|nullable|string|max:255',
            'emitido_en'              => 'required|date',
        ]);

        $ticket->update($validated);

        // Si es boleta y no se proporcionó datos de facturación explícitos, usar los del pasajero
        if ($ticket->tipo_documento === 'BOLETA' && empty($ticket->documento_facturacion)) {
            $ticket->update([
                'documento_facturacion' => $ticket->dni_pasajero,
                'nombre_facturacion' => $ticket->nombre_pasajero,
            ]);
        }

        // Send to greenter if applicable
        if (in_array($ticket->tipo_documento, ['BOLETA', 'FACTURA'])) {
            try {
                $res = $ticket->tipo_documento === 'BOLETA'
                    ? $this->greenter->emitirBoleta($ticket)
                    : $this->greenter->emitirFactura($ticket);

                if ($res['status']) {
                    $ticket->update([
                        'serie_cpe'       => $res['serie'],
                        'correlativo_cpe' => $res['correlativo'],
                        'cdr_status'      => $res['cdr'],
                        'sincronizado'    => true,
                        'sincronizado_en' => now(),
                    ]);
                }
            } catch (\Exception $e) {
                // Keep the state, can be synced later
            }
        }

        return back()->with('success', 'Reservación confirmada correctamente.');
    }

}
