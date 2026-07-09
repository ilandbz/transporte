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
        ]);

        $vehicle = Vehicle::findOrFail($validated['vehicle_id']);

        $trip = Trip::create([
            ...$validated,
            'user_id'        => auth()->id(),
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
}
