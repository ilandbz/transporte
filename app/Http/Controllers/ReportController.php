<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\Vehicle;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function liquidacion(Request $request): Response
    {
        $fecha = $request->get('fecha', today()->toDateString());

        $query = Ticket::whereDate('emitido_en', $fecha);

        if ($request->filled('vehicle_id')) {
            $query->whereHas('trip', fn($q) => $q->where('vehicle_id', $request->vehicle_id));
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $tickets = $query->with(['trip'])->orderBy('emitido_en')->get();

        $resumen = [
            'total_efectivo'      => $tickets->where('metodo_pago', 'efectivo')->sum('precio'),
            'total_yape'          => $tickets->where('metodo_pago', 'yape')->sum('precio'),
            'total_plin'          => $tickets->where('metodo_pago', 'plin')->sum('precio'),
            'total_transferencia' => $tickets->where('metodo_pago', 'transferencia')->sum('precio'),
            'total_general'       => $tickets->sum('precio'),
            'cantidad_tickets'    => $tickets->count(),
            'cantidad_encomiendas'=> 0,
        ];

        $vehiculos  = Vehicle::activo()->get(['id', 'placa']);
        $conductores = User::where('role', 'conductor')->get(['id', 'name']);
        $filtros    = $request->only(['fecha', 'vehicle_id', 'user_id']);

        return Inertia::render('Reports/LiquidacionCaja', compact(
            'resumen', 'tickets', 'vehiculos', 'conductores', 'filtros'
        ));
    }
}
