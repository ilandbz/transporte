<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\Trip;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $hoy = today();

        $stats = [
            'viajes_hoy'              => Trip::whereDate('fecha_salida', $hoy)->count(),
            'tickets_hoy'             => Ticket::whereDate('created_at', $hoy)->count(),
            'ingresos_hoy'            => Ticket::whereDate('created_at', $hoy)->sum('precio'),
            'tickets_pendientes_sync' => Ticket::where('sincronizado', false)->count(),
            'cpe_rechazados'          => Ticket::whereNotNull('cdr_status')
                                               ->where('cdr_status', '!=', '0')->count(),
        ];

        $viajes_activos = Trip::with(['route', 'vehicle', 'conductor'])
            ->whereDate('fecha_salida', $hoy)
            ->whereIn('estado', ['abierto', 'en_ruta'])
            ->get();

        return Inertia::render('Dashboard/Index', compact('stats', 'viajes_activos'));
    }
}
