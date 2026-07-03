<?php
namespace App\Http\Controllers;

use App\Models\Route as RouteModel;
use App\Models\RouteTariff;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RouteWebController extends Controller
{
    public function index(): Response
    {
        $routes = RouteModel::withCount('tariffs')
            ->with('tariffs')
            ->orderBy('nombre')
            ->get();

        return Inertia::render('Settings/Routes/Index', compact('routes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre'         => 'required|string|max:100',
            'origen'         => 'required|string|max:100',
            'destino'        => 'required|string|max:100',
            'ubigeo_origen'  => 'required|string|size:6',
            'ubigeo_destino' => 'required|string|size:6',
            'paradas'        => 'nullable|array',
        ]);

        RouteModel::create($validated);
        return back()->with('success', 'Ruta creada.');
    }

    public function toggleActivo(RouteModel $route)
    {
        $route->update(['activo' => !$route->activo]);
        return back()->with('success', 'Estado de ruta actualizado.');
    }

    public function storeTariff(Request $request, RouteModel $route)
    {
        $validated = $request->validate([
            'origen_tramo'   => 'required|string',
            'destino_tramo'  => 'required|string',
            'ubigeo_origen'  => 'required|string|size:6',
            'ubigeo_destino' => 'required|string|size:6',
            'precio'         => 'required|numeric|min:0',
            'clase'          => 'required|in:normal,vip',
        ]);

        $route->tariffs()->updateOrCreate(
            [
                'origen_tramo'  => $validated['origen_tramo'],
                'destino_tramo' => $validated['destino_tramo'],
                'clase'         => $validated['clase'],
            ],
            $validated
        );

        return back()->with('success', 'Tarifa guardada.');
    }

    public function updateTariff(Request $request, RouteTariff $tariff)
    {
        $validated = $request->validate([
            'origen_tramo'   => 'required|string',
            'destino_tramo'  => 'required|string',
            'ubigeo_origen'  => 'required|string|size:6',
            'ubigeo_destino' => 'required|string|size:6',
            'precio'         => 'required|numeric|min:0',
            'clase'          => 'required|in:normal,vip',
        ]);

        $tariff->update($validated);

        return back()->with('success', 'Tarifa actualizada.');
    }

    public function destroyTariff(RouteTariff $tariff)
    {
        $tariff->delete();
        return back()->with('success', 'Tarifa eliminada.');
    }
}
