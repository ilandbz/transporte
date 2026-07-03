<?php
namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VehicleWebController extends Controller
{
    public function index(): Response
    {
        $vehicles = Vehicle::orderBy('placa')->get();
        return Inertia::render('Settings/Vehicles/Index', compact('vehicles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'placa'              => 'required|string|max:10|unique:vehicles,placa',
            'marca'              => 'nullable|string|max:50',
            'modelo'             => 'nullable|string|max:50',
            'tipo'               => 'required|in:minivan,bus,coaster',
            'capacidad_asientos' => 'required|integer|min:1|max:60',
        ]);

        // Generar layout_asientos automático según capacidad
        $validated['layout_asientos'] = $this->generarLayout(
            $validated['capacidad_asientos'],
            $validated['tipo']
        );

        Vehicle::create($validated);
        return back()->with('success', 'Vehículo registrado.');
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $validated = $request->validate([
            'placa'              => 'required|string|max:10|unique:vehicles,placa,' . $vehicle->id,
            'marca'              => 'nullable|string|max:50',
            'modelo'             => 'nullable|string|max:50',
            'tipo'               => 'required|in:minivan,bus,coaster',
            'capacidad_asientos' => 'required|integer|min:1|max:60',
            'activo'             => 'boolean',
        ]);

        $vehicle->update($validated);
        return back()->with('success', 'Vehículo actualizado.');
    }

    public function toggleActivo(Vehicle $vehicle)
    {
        $vehicle->update(['activo' => !$vehicle->activo]);
        return back()->with('success', $vehicle->activo ? 'Vehículo activado.' : 'Vehículo desactivado.');
    }

    private function generarLayout(int $capacidad, string $tipo): array
    {
        $asientos = range(1, $capacidad);
        $filas    = (int) ceil($capacidad / ($tipo === 'minivan' ? 2 : 4));
        return [
            'filas'    => $filas,
            'columnas' => $tipo === 'minivan' ? 2 : 4,
            'pasillo'  => $tipo === 'minivan' ? null : 2,
            'asientos' => $asientos,
        ];
    }
}
