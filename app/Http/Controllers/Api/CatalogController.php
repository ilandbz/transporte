<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Route;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    /**
     * GET /api/v1/routes
     * Retorna las rutas activas con sus tramos y tarifas anidadas.
     */
    public function routes(Request $request): JsonResponse
    {
        $routes = Route::where('activo', true)->with('tariffs')->get();

        $formatted = $routes->map(function ($route) {
            $tramosGrouped = collect($route->tariffs)->groupBy(function ($tariff) {
                return $tariff->origen_tramo . '|' . $tariff->destino_tramo;
            });

            $tramos = [];
            foreach ($tramosGrouped as $key => $tarifas) {
                [$origen, $destino] = explode('|', $key);
                $tramos[] = [
                    'origen'  => $origen,
                    'destino' => $destino,
                    'tarifas' => $tarifas->map(function ($t) {
                        return [
                            'clase'  => $t->clase,
                            'precio' => (float)$t->precio,
                        ];
                    })->values()->all(),
                ];
            }

            return [
                'id'     => $route->id,
                'nombre' => $route->nombre,
                'tramos' => $tramos,
            ];
        });

        return response()->json($formatted);
    }

    /**
     * GET /api/v1/vehicles?activo=1
     * Retorna vehículos filtrando por estado, incluye layout_asientos.
     */
    public function vehicles(Request $request): JsonResponse
    {
        $query = Vehicle::query();

        if ($request->has('activo')) {
            $activo = filter_var($request->query('activo'), FILTER_VALIDATE_BOOLEAN);
            $query->where('activo', $activo);
        }

        $vehicles = $query->get();

        return response()->json($vehicles);
    }

    /**
     * GET /api/v1/branches
     * Retorna las sucursales activas.
     */
    public function branches(Request $request): JsonResponse
    {
        $branches = Branch::where('activo', true)->get();

        return response()->json($branches);
    }
}
