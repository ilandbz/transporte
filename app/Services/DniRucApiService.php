<?php

namespace App\Services;

use App\Models\DniCache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DniRucApiService
{
    public function consultarDni(string $dni): ?array
    {
        // Buscar en caché local
        $cache = DniCache::where('tipo', 'dni')->where('numero', $dni)->first();
        if ($cache) {
            return ['numero' => $dni, 'nombre' => $cache->nombre, 'extra' => $cache->extra_json];
        }

        try {
            $url   = env('DNI_API_URL');
            $token = env('DNI_API_TOKEN');

            if (!$url || !$token) {
                return null;
            }

            $response = Http::timeout(5)->withToken($token)->get("{$url}/{$dni}");

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();

            DniCache::updateOrCreate(
                ['tipo' => 'dni', 'numero' => $dni],
                [
                    'nombre'        => trim(($data['nombres'] ?? '') . ' ' . ($data['apellidoPaterno'] ?? '') . ' ' . ($data['apellidoMaterno'] ?? '')),
                    'extra_json'    => $data,
                    'consultado_en' => now(),
                ]
            );

            return [
                'numero'    => $dni,
                'nombre'    => $data['nombres'] ?? null,
                'apellidos' => trim(($data['apellidoPaterno'] ?? '') . ' ' . ($data['apellidoMaterno'] ?? '')),
            ];
        } catch (\Exception $e) {
            Log::warning("DniRucApiService: fallo DNI {$dni} — " . $e->getMessage());
            return null;
        }
    }

    public function consultarRuc(string $ruc): ?array
    {
        $cache = DniCache::where('tipo', 'ruc')->where('numero', $ruc)->first();
        if ($cache) {
            return ['numero' => $ruc, 'razon_social' => $cache->nombre, 'extra' => $cache->extra_json];
        }

        try {
            $url   = env('DNI_API_URL');
            $token = env('DNI_API_TOKEN');

            if (!$url || !$token) {
                return null;
            }

            $response = Http::timeout(5)->withToken($token)->get("{$url}/ruc/{$ruc}");

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();

            DniCache::updateOrCreate(
                ['tipo' => 'ruc', 'numero' => $ruc],
                [
                    'nombre'        => $data['razonSocial'] ?? $data['nombre'] ?? '',
                    'extra_json'    => $data,
                    'consultado_en' => now(),
                ]
            );

            return [
                'numero'       => $ruc,
                'razon_social' => $data['razonSocial'] ?? null,
                'direccion'    => $data['direccion'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::warning("DniRucApiService: fallo RUC {$ruc} — " . $e->getMessage());
            return null;
        }
    }
}
