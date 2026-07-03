<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Services\GpsTrackingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class GpsController extends Controller
{
    public function __construct(private GpsTrackingService $gpsService) {}

    // POST /api/v1/trips/{trip}/gps
    public function store(Request $request, Trip $trip): JsonResponse
    {
        $validated = $request->validate([
            'lat'           => 'required|numeric|between:-90,90',
            'lng'           => 'required|numeric|between:-180,180',
            'velocidad_kmh' => 'nullable|numeric|min:0',
        ]);

        $this->gpsService->store(
            $trip,
            $validated['lat'],
            $validated['lng'],
            $validated['velocidad_kmh'] ?? null
        );

        return response()->json(['ok' => true], 201);
    }
}
