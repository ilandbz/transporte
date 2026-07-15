<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Route;
use App\Models\RouteTariff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Todos los endpoints aquí son exclusivos del rol 'admin'. Espejan la
 * misma lógica de negocio que RouteWebController (panel web), para que
 * el admin pueda gestionar rutas/tarifas también desde la app móvil.
 */
class RouteManagementController extends Controller
{
    private function ensureAdmin(): ?JsonResponse
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'No autorizado. Solo administradores.'], 403);
        }
        return null;
    }

    // GET /api/v1/admin/routes — Listado completo con tarifas (incluye IDs, para editar/eliminar)
    public function index(Request $request): JsonResponse
    {
        if ($denegado = $this->ensureAdmin()) return $denegado;

        $routes = Route::withCount('tariffs')->with('tariffs')->orderBy('nombre')->get();

        return response()->json($routes);
    }

    // POST /api/v1/admin/routes — Crear ruta
    public function store(Request $request): JsonResponse
    {
        if ($denegado = $this->ensureAdmin()) return $denegado;

        $validated = $request->validate([
            'nombre'         => 'required|string|max:100',
            'origen'         => 'required|string|max:100',
            'destino'        => 'required|string|max:100',
            'ubigeo_origen'  => 'required|string|size:6',
            'ubigeo_destino' => 'required|string|size:6',
            'paradas'        => 'nullable|array',
        ]);

        $route = Route::create($validated);

        return response()->json($route, 201);
    }

    // PATCH /api/v1/admin/routes/{route}/toggle — Activar/desactivar ruta
    public function toggleActivo(Route $route): JsonResponse
    {
        if ($denegado = $this->ensureAdmin()) return $denegado;

        $route->update(['activo' => !$route->activo]);

        return response()->json($route);
    }

    // POST /api/v1/admin/routes/{route}/tariffs — Agregar tramo+tarifa a una ruta
    public function storeTariff(Request $request, Route $route): JsonResponse
    {
        if ($denegado = $this->ensureAdmin()) return $denegado;

        $validated = $request->validate([
            'origen_tramo'   => 'required|string',
            'destino_tramo'  => 'required|string',
            'ubigeo_origen'  => 'required|string|size:6',
            'ubigeo_destino' => 'required|string|size:6',
            'precio'         => 'required|numeric|min:0',
            'clase'          => 'required|in:normal,vip',
        ]);

        $tariff = $route->tariffs()->updateOrCreate(
            [
                'origen_tramo'  => $validated['origen_tramo'],
                'destino_tramo' => $validated['destino_tramo'],
                'clase'         => $validated['clase'],
            ],
            $validated
        );

        return response()->json($tariff, 201);
    }

    // PUT /api/v1/admin/routes/tariffs/{tariff} — Editar un tramo+tarifa
    public function updateTariff(Request $request, RouteTariff $tariff): JsonResponse
    {
        if ($denegado = $this->ensureAdmin()) return $denegado;

        $validated = $request->validate([
            'origen_tramo'   => 'required|string',
            'destino_tramo'  => 'required|string',
            'ubigeo_origen'  => 'required|string|size:6',
            'ubigeo_destino' => 'required|string|size:6',
            'precio'         => 'required|numeric|min:0',
            'clase'          => 'required|in:normal,vip',
        ]);

        $tariff->update($validated);

        return response()->json($tariff);
    }

    // DELETE /api/v1/admin/routes/tariffs/{tariff} — Eliminar un tramo+tarifa
    public function destroyTariff(RouteTariff $tariff): JsonResponse
    {
        if ($denegado = $this->ensureAdmin()) return $denegado;

        $tariff->delete();

        return response()->json(['ok' => true]);
    }
}
