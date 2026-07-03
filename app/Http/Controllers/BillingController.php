<?php
namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Services\SunatGreenterService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    public function __construct(private SunatGreenterService $sunat) {}

    public function consolaCpe(Request $request): Response
    {
        $query = Ticket::with(['trip.vehicle', 'vendedor'])
            ->orderByDesc('emitido_en');

        if ($request->filled('fecha_desde')) {
            $query->whereDate('emitido_en', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('emitido_en', '<=', $request->fecha_hasta);
        }
        if ($request->filled('tipo_documento')) {
            $query->where('tipo_documento', $request->tipo_documento);
        }
        if ($request->filled('estado')) {
            match($request->estado) {
                'aceptado'       => $query->where('cdr_status', '0'),
                'rechazado'      => $query->whereNotNull('cdr_status')->where('cdr_status', '!=', '0'),
                'pendiente'      => $query->whereNull('cdr_status')->where('tipo_documento', '!=', 'TICKET_INTERNO'),
                'ticket_interno' => $query->where('tipo_documento', 'TICKET_INTERNO'),
                default          => null,
            };
        }

        $tickets = $query->paginate(20)->withQueryString();
        $filtros  = $request->only(['fecha_desde','fecha_hasta','tipo_documento','estado']);

        // Stats del header
        $stats = [
            'aceptados'      => Ticket::where('cdr_status', '0')->count(),
            'rechazados'     => Ticket::whereNotNull('cdr_status')->where('cdr_status','!=','0')->count(),
            'pendientes'     => Ticket::whereNull('cdr_status')->where('tipo_documento','!=','TICKET_INTERNO')->count(),
            'tickets_intern' => Ticket::where('tipo_documento','TICKET_INTERNO')->count(),
        ];

        return Inertia::render('Billing/ConsolaCpe', compact('tickets','filtros','stats'));
    }

    public function reintentarCpe(Ticket $ticket)
    {
        if ($ticket->tipo_documento === 'TICKET_INTERNO') {
            $ticket->update(['tipo_documento' => 'BOLETA']);
        }

        $resultado = $this->sunat->emitirBoleta($ticket);

        if ($resultado['status']) {
            $ticket->update([
                'serie_cpe'       => $resultado['serie'],
                'correlativo_cpe' => $resultado['correlativo'],
                'cdr_status'      => $resultado['cdr'],
                'sincronizado'    => true,
                'sincronizado_en' => now(),
            ]);
            return back()->with('success', "CPE {$resultado['serie']}-{$resultado['correlativo']} emitido correctamente.");
        }

        return back()->with('error', 'Error al emitir: ' . ($resultado['error'] ?? 'Error desconocido'));
    }
}
