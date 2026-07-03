<?php
namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class SyncPanelController extends Controller
{
    public function index(): Response
    {
        $pendientesPorConductor = Ticket::where('sincronizado', false)
            ->with('vendedor')
            ->get()
            ->groupBy('user_id')
            ->map(function ($tickets) {
                $conductor = $tickets->first()->vendedor;
                return [
                    'conductor_id'     => $conductor?->id,
                    'conductor_nombre' => $conductor?->name ?? 'Desconocido',
                    'conductor_email'  => $conductor?->email ?? '',
                    'cantidad'         => $tickets->count(),
                    'monto_total'      => (float) $tickets->sum('precio'),
                    'mas_antiguo'      => $tickets->min('emitido_en'),
                    'mas_reciente'     => $tickets->max('emitido_en'),
                    'tickets'          => $tickets->map(fn($t) => [
                        'id'           => $t->id,
                        'uuid_local'   => $t->uuid_local,
                        'origen_tramo' => $t->origen_tramo,
                        'destino_tramo'=> $t->destino_tramo,
                        'precio'       => $t->precio,
                        'emitido_en'   => $t->emitido_en,
                    ])->values(),
                ];
            })
            ->values();

        return Inertia::render('Billing/SyncPanel', [
            'pendientes'        => $pendientesPorConductor,
            'total_pendientes'  => Ticket::where('sincronizado', false)->count(),
            'monto_total'       => (float) Ticket::where('sincronizado', false)->sum('precio'),
        ]);
    }
}
