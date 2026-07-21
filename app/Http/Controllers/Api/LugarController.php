<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lugar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LugarController extends Controller
{
    // GET /api/v1/lugares
    public function index(): JsonResponse
    {
        return response()->json(
            Lugar::where('activo', true)->orderBy('nombre')->get(['id', 'nombre'])
        );
    }

    // GET /api/v1/admin/lugares — Solo admin, incluye inactivos
    public function adminIndex(): JsonResponse
    {
        if ($this->noEsAdmin()) return $this->errorNoAutorizado();

        return response()->json(Lugar::orderBy('nombre')->get());
    }

    // POST /api/v1/admin/lugares — Solo admin
    public function store(Request $request): JsonResponse
    {
        if ($this->noEsAdmin()) return $this->errorNoAutorizado();

        $validated = $request->validate([
            'nombre' => 'required|string|max:150|unique:lugars,nombre',
        ]);

        $lugar = Lugar::create(['nombre' => strtoupper($validated['nombre'])]);

        return response()->json($lugar, 201);
    }

    // PATCH /api/v1/admin/lugares/{lugar}/toggle — Solo admin
    public function toggle(Lugar $lugar): JsonResponse
    {
        if ($this->noEsAdmin()) return $this->errorNoAutorizado();

        $lugar->update(['activo' => !$lugar->activo]);

        return response()->json($lugar);
    }

    private function noEsAdmin(): bool
    {
        return auth()->user()->role !== 'admin';
    }

    private function errorNoAutorizado(): JsonResponse
    {
        return response()->json(['error' => 'No autorizado. Solo administradores.'], 403);
    }
}
