<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GpsTrack;
use App\Models\Jornada;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JornadaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Jornada::with(['vehicle', 'conductor']);

        if ($request->boolean('mine')) {
            $query->where('user_id', auth()->id());
        }

        return response()->json($query->orderBy('created_at', 'desc')->get());
    }

    // POST /api/v1/jornadas — Iniciar jornada (vehículo + GPS)
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'fecha'      => 'nullable|date',
            'lat'        => 'nullable|numeric|between:-90,90',
            'lng'        => 'nullable|numeric|between:-180,180',
        ]);

        $jornada = Jornada::create([
            'user_id'    => auth()->id(),
            'vehicle_id' => $validated['vehicle_id'],
            'fecha'      => $validated['fecha'] ?? now()->toDateString(),
            'estado'     => 'activa',
            'lat_inicio' => $validated['lat'] ?? null,
            'lng_inicio' => $validated['lng'] ?? null,
            'iniciado_en' => now(),
        ]);

        return response()->json($jornada->load(['vehicle', 'conductor']), 201);
    }

    // PATCH /api/v1/jornadas/{jornada}/cerrar
    public function cerrar(Jornada $jornada): JsonResponse
    {
        if ($jornada->user_id !== auth()->id()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }
        if ($jornada->estado === 'cerrada') {
            return response()->json(['error' => 'Esta jornada ya está cerrada.'], 400);
        }

        $jornada->update(['estado' => 'cerrada', 'cerrado_en' => now()]);

        return response()->json($jornada);
    }

    // POST /api/v1/jornadas/{jornada}/gps
    public function storeGps(Request $request, Jornada $jornada): JsonResponse
    {
        if ($jornada->user_id !== auth()->id()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'lat'           => 'required|numeric|between:-90,90',
            'lng'           => 'required|numeric|between:-180,180',
            'velocidad_kmh' => 'nullable|numeric|min:0',
        ]);

        $punto = GpsTrack::create([
            'jornada_id'    => $jornada->id,
            'lat'           => $validated['lat'],
            'lng'           => $validated['lng'],
            'velocidad_kmh' => $validated['velocidad_kmh'] ?? null,
            'registrado_en' => now(),
        ]);

        return response()->json($punto, 201);
    }

    // GET /api/v1/jornadas/{jornada}/gps
    public function gps(Jornada $jornada): JsonResponse
    {
        $puntos = $jornada->gpsTracks()->orderBy('registrado_en', 'asc')->get();

        return response()->json($puntos->map(fn($p) => [
            'lat'           => (float) $p->lat,
            'lng'           => (float) $p->lng,
            'velocidad_kmh' => $p->velocidad_kmh !== null ? (float) $p->velocidad_kmh : null,
            'registrado_en' => $p->registrado_en,
        ]));
    }
}
