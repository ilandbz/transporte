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
        
        $activeBranchId = ($request->user()->role === 'admin' && session('active_branch_id'))
            ? session('active_branch_id')
            : $request->user()->branch_id;

        $query = Ticket::whereDate('emitido_en', $fecha);
        $packagesQuery = \App\Models\Package::whereDate('emitido_en', $fecha);

        if ($activeBranchId) {
            $query->where('branch_id', $activeBranchId);
            $packagesQuery->where('branch_id', $activeBranchId);
        }

        if ($request->filled('vehicle_id')) {
            $query->whereHas('trip', fn($q) => $q->where('vehicle_id', $request->vehicle_id));
            $packagesQuery->whereHas('trip', fn($q) => $q->where('vehicle_id', $request->vehicle_id));
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
            $packagesQuery->where('user_id', $request->user_id);
        }

        $tickets = $query->with(['trip'])->orderBy('emitido_en')->get();
        $packages = $packagesQuery->orderBy('emitido_en')->get();

        $resumen = [
            'total_efectivo'      => $tickets->where('metodo_pago', 'efectivo')->sum('precio') + $packages->sum('precio'), // simplificado, asumiendo que encomiendas se pagan en efectivo en caja, o deberiamos agregar metodo de pago a encomiendas. Por ahora se suma al general.
            'total_yape'          => $tickets->where('metodo_pago', 'yape')->sum('precio'),
            'total_plin'          => $tickets->where('metodo_pago', 'plin')->sum('precio'),
            'total_transferencia' => $tickets->where('metodo_pago', 'transferencia')->sum('precio'),
            'total_general'       => $tickets->sum('precio') + $packages->sum('precio'),
            'cantidad_tickets'    => $tickets->count(),
            'cantidad_encomiendas'=> $packages->count(),
        ];

        $vehiculos  = Vehicle::activo()->get(['id', 'placa']);
        $conductores = User::where('role', 'conductor')->get(['id', 'name']);
        $filtros    = $request->only(['fecha', 'vehicle_id', 'user_id']);

        return Inertia::render('Reports/LiquidacionCaja', compact(
            'resumen', 'tickets', 'packages', 'vehiculos', 'conductores', 'filtros'
        ));
    }
}
