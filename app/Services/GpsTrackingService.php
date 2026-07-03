<?php

namespace App\Services;

use App\Models\GpsTrack;
use App\Models\Trip;
use Illuminate\Support\Facades\Cache;

class GpsTrackingService
{
    public function store(Trip $trip, float $lat, float $lng, ?float $speed = null): void
    {
        GpsTrack::create([
            'trip_id'       => $trip->id,
            'lat'           => $lat,
            'lng'           => $lng,
            'velocidad_kmh' => $speed,
            'registrado_en' => now(),
        ]);

        Cache::put("gps:trip:{$trip->id}", [
            'lat'   => $lat,
            'lng'   => $lng,
            'speed' => $speed,
            'at'    => now()->toISOString(),
        ], 300);
    }

    public function getLastPosition(Trip $trip): ?array
    {
        $cached = Cache::get("gps:trip:{$trip->id}");
        if ($cached) {
            return $cached;
        }

        $track = GpsTrack::where('trip_id', $trip->id)
            ->latest('registrado_en')
            ->first();

        return $track
            ? $track->only(['lat', 'lng', 'velocidad_kmh', 'registrado_en'])
            : null;
    }
}
