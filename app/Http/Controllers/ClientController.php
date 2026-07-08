<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\DniRucApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    public function __construct(private DniRucApiService $dniService) {}

    public function index(Request $request): Response
    {
        $query = Client::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('documento', 'like', "%{$search}%")
                  ->orWhere('nombre', 'like', "%{$search}%");
        }

        $clientes = $query->orderByDesc('updated_at')->paginate(20)->withQueryString();
        
        return Inertia::render('Clients/Index', [
            'clientes' => $clientes,
            'filters' => $request->only('search')
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'documento' => 'required|string|max:20|unique:clients',
            'nombre' => 'required|string|max:255',
            'telefono' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        Client::create($validated);
        return back()->with('success', 'Cliente creado correctamente.');
    }

    public function update(Request $request, Client $cliente)
    {
        $validated = $request->validate([
            'documento' => 'required|string|max:20|unique:clients,documento,' . $cliente->id,
            'nombre' => 'required|string|max:255',
            'telefono' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        $cliente->update($validated);
        return back()->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(Client $cliente)
    {
        $cliente->delete();
        return back()->with('success', 'Cliente eliminado correctamente.');
    }

    public function search(string $documento): JsonResponse
    {
        // 1. Buscar en BD local (clientes frecuentes)
        $client = Client::where('documento', $documento)->first();

        if ($client) {
            return response()->json([
                'encontrado' => true,
                'origen' => 'local',
                'datos' => [
                    'numero' => $client->documento,
                    'nombre' => $client->nombre,
                    'telefono' => $client->telefono,
                ]
            ]);
        }

        // 2. Si no existe, buscar en API DNI/RUC usando el servicio existente
        if (strlen($documento) === 8) {
            $result = $this->dniService->consultarDni($documento);
        } elseif (strlen($documento) === 11) {
            $result = $this->dniService->consultarRuc($documento);
        } else {
            $result = null;
        }

        if ($result) {
            return response()->json([
                'encontrado' => true,
                'origen' => 'api',
                'datos' => [
                    'numero' => $result['numero'],
                    'nombre' => $result['nombre'] ?? $result['razon_social'] ?? '',
                    'telefono' => '', // La API no da teléfono
                ]
            ]);
        }

        return response()->json(['encontrado' => false], 404);
    }
}
