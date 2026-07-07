<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Models\Vehicle;
use App\Models\Route;
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

        $routes = Route::where('activo', true)->get(['id', 'nombre', 'origen', 'destino']);
        $vehicles = Vehicle::where('activo', true)->get(['id', 'placa', 'capacidad_asientos']);

        return Inertia::render('Trips/Index', compact('trips', 'fecha', 'routes', 'vehicles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'route_id'          => 'required|exists:routes,id',
            'vehicle_id'        => 'required|exists:vehicles,id',
            'fecha_salida'      => 'required|date',
            'numero_manifiesto' => 'nullable|string|max:20',
        ]);

        $vehicle = Vehicle::findOrFail($validated['vehicle_id']);

        Trip::create([
            ...$validated,
            'user_id'        => auth()->id(),
            'placa_vehiculo' => $vehicle->placa,
            'estado'         => 'abierto',
            'asientos_ocupados' => [],
        ]);

        return back()->with('success', 'Viaje registrado correctamente.');
    }

    public function destroy(Trip $trip)
    {
        if ($trip->tickets()->count() > 0) {
            return back()->with('error', 'No se puede eliminar un viaje que ya tiene tickets vendidos.');
        }

        $trip->delete();

        return back()->with('success', 'Viaje eliminado correctamente.');
    }
}
