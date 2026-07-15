<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\Vehicle;
use App\Models\Route;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TripController extends Controller
{
    // GET /api/v1/trips
    public function index(Request $request): JsonResponse
    {
        $query = Trip::with(['route', 'vehicle', 'conductor']);

        if ($request->has('estado')) {
            $query->where('estado', $request->query('estado'));
        }

        if ($request->query('mine') == '1') {
            $query->where('user_id', auth()->id());
        }

        if ($request->has('fecha')) {
            $query->whereDate('fecha_salida', $request->query('fecha'));
        }

        $trips = $query->orderBy('created_at', 'desc')->get();

        return response()->json($trips);
    }

    // GET /api/v1/trips/{trip}
    public function show(Trip $trip): JsonResponse
    {
        $trip->load(['route', 'vehicle', 'conductor']);
        $trip->loadCount(['tickets', 'packages']);

        return response()->json($trip);
    }

    // POST /api/v1/trips — Abrir manifiesto
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'route_id'          => 'required|exists:routes,id',
            'vehicle_id'        => 'required|exists:vehicles,id',
            'fecha_salida'      => 'required|date',
            'numero_manifiesto' => 'nullable|string|max:20',
            'conductor_id'      => 'nullable|exists:users,id',
        ]);

        // Solo un admin puede abrir un viaje "para" otro usuario (asignar
        // conductor). Cualquier otro rol siempre queda como conductor de
        // su propio viaje, ignorando 'conductor_id' si lo mandaran.
        $conductorId = (auth()->user()->role === 'admin' && !empty($validated['conductor_id']))
            ? $validated['conductor_id']
            : auth()->id();

        $vehicle = Vehicle::findOrFail($validated['vehicle_id']);

        $trip = Trip::create([
            'route_id'          => $validated['route_id'],
            'vehicle_id'        => $validated['vehicle_id'],
            'fecha_salida'      => $validated['fecha_salida'],
            'numero_manifiesto' => $validated['numero_manifiesto'] ?? null,
            'user_id'        => $conductorId,
            'placa_vehiculo' => $vehicle->placa,
            'estado'         => 'abierto',
            'asientos_ocupados' => [],
        ]);

        return response()->json($trip->load(['route', 'vehicle', 'conductor']), 201);
    }

    // PATCH /api/v1/trips/{trip}/close — Cerrar manifiesto
    public function close(Request $request, Trip $trip): JsonResponse
    {
        if ($trip->user_id !== auth()->id()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $trip->update(['estado' => 'cerrado']);

        return response()->json($trip);
    }

    // PATCH /api/v1/trips/{trip}/start — Iniciar ruta (abierto -> en_ruta)
    public function start(Request $request, Trip $trip): JsonResponse
    {
        if ($trip->user_id !== auth()->id()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        if ($trip->estado !== 'abierto') {
            return response()->json(['error' => 'Solo se puede iniciar un viaje que esté abierto.'], 400);
        }

        $validated = $request->validate([
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
        ]);

        $trip->update([
            'estado'      => 'en_ruta',
            'lat_inicio'  => $validated['lat'] ?? null,
            'lng_inicio'  => $validated['lng'] ?? null,
        ]);

        return response()->json($trip);
    }

    // GET /api/v1/trips/{trip}/seats — Estado de asientos
    public function seats(Trip $trip): JsonResponse
    {
        $vehicle = $trip->vehicle;
        $total = $vehicle->capacidad_asientos;
        $ocupados = $trip->asientos_ocupados ?? [];
        $disponibles = array_values(
            array_diff(range(1, $total), $ocupados)
        );

        return response()->json([
            'total'           => $total,
            'ocupados'        => $ocupados,
            'disponibles'     => $disponibles,
            'layout_asientos' => $vehicle->layout_asientos ?? null,
        ]);
    }

    // GET /api/v1/trips/{trip}/tickets — Listado de pasajes vendidos en este viaje
    public function tickets(Trip $trip): JsonResponse
    {
        $tickets = $trip->tickets()->orderBy('created_at', 'desc')->get();

        return response()->json(\App\Http\Resources\TicketResource::collection($tickets));
    }

    // GET /api/v1/trips/{trip}/packages — Listado de encomiendas registradas en este viaje
    public function packages(Trip $trip): JsonResponse
    {
        $packages = $trip->packages()->orderBy('created_at', 'desc')->get();

        return response()->json(\App\Http\Resources\PackageResource::collection($packages));
    }

    // GET /api/v1/trips/{trip}/gps — Historial de posiciones GPS del viaje
    public function gps(Trip $trip): JsonResponse
    {
        $puntos = $trip->gpsTracks()->orderBy('registrado_en', 'asc')->get();

        return response()->json($puntos->map(fn($p) => [
            'lat'           => (float) $p->lat,
            'lng'           => (float) $p->lng,
            'velocidad_kmh' => $p->velocidad_kmh !== null ? (float) $p->velocidad_kmh : null,
            'registrado_en' => $p->registrado_en,
        ]));
    }
}
