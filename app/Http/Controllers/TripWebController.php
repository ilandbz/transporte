<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TripWebController extends Controller
{
    public function index(Request $request): Response
    {
        $fecha = $request->get('fecha', today()->toDateString());

        $trips = Trip::with(['route', 'vehicle', 'conductor'])
            ->whereDate('fecha_salida', $fecha)
            ->withCount('tickets')
            ->orderBy('fecha_salida')
            ->get();

        return Inertia::render('Trips/Index', compact('trips', 'fecha'));
    }
}
