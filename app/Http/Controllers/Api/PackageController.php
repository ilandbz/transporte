<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PackageResource;
use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PackageController extends Controller
{
    // POST /api/v1/packages — Registrar encomienda
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'trip_id'               => 'required|exists:trips,id',
            'uuid_local'            => 'required|uuid',
            'remitente_nombre'      => 'required|string|max:200',
            'remitente_dni'         => 'nullable|string|max:15',
            'remitente_telefono'    => 'nullable|string|max:15',
            'destinatario_nombre'   => 'required|string|max:200',
            'destinatario_dni'      => 'nullable|string|max:15',
            'destinatario_telefono' => 'nullable|string|max:15',
            'descripcion'           => 'required|string|max:500',
            'peso_kg'               => 'nullable|numeric|min:0.1',
            'cantidad_bultos'       => 'required|integer|min:1',
            'precio'                => 'required|numeric|min:0',
            'estado_pago'           => 'required|in:pagado,por_cobrar',
            'branch_id'             =>  auth()->user()->branch_id,
            'emitido_en'            => 'required|date',
        ]);

        $package = Package::create([
            ...$validated,
            'user_id'  => auth()->id(),
            'estado'   => 'en_transito',
            'qr_code'  => hash('sha256', $validated['uuid_local']),
            'sincronizado'            => false,
            'emitido_en_contingencia' => false,
            'tipo_documento'          => 'TICKET_INTERNO',
        ]);

        return response()->json(new PackageResource($package), 201);
    }

    // GET /api/v1/packages/{package}/qr
    public function qr(Package $package): JsonResponse
    {
        return response()->json([
            'qr_code'     => $package->qr_code,
            'package_id'  => $package->id,
            'descripcion' => $package->descripcion,
            'remitente'   => $package->remitente_nombre,
            'destinatario' => $package->destinatario_nombre,
        ]);
    }

    // PATCH /api/v1/packages/{package}/deliver
    public function deliver(Request $request, Package $package): JsonResponse
    {
        if ($package->trip->user_id !== auth()->id()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $package->update([
            'estado'       => 'entregado',
            'entregado_en' => now(),
        ]);

        return response()->json(new PackageResource($package));
    }

    // PATCH /api/v1/packages/{package}
    public function update(Request $request, Package $package): JsonResponse
    {
        if ($package->estado === 'entregado') {
            return response()->json(['error' => 'No se puede editar una encomienda entregada.'], 400);
        }

        $validated = $request->validate([
            'remitente_nombre'      => 'sometimes|required|string|max:200',
            'remitente_dni'         => 'nullable|string|max:15',
            'remitente_telefono'    => 'nullable|string|max:15',
            'destinatario_nombre'   => 'sometimes|required|string|max:200',
            'destinatario_dni'      => 'nullable|string|max:15',
            'destinatario_telefono' => 'nullable|string|max:15',
            'descripcion'           => 'sometimes|required|string|max:500',
            'peso_kg'               => 'nullable|numeric|min:0.1',
            'cantidad_bultos'       => 'sometimes|required|integer|min:1',
            'precio'                => 'sometimes|required|numeric|min:0',
            'estado_pago'           => 'sometimes|required|in:pagado,por_cobrar',
        ]);

        $package->update($validated);

        return response()->json(new PackageResource($package));
    }

    // PATCH /api/v1/packages/{package}/anular
    public function anular(Request $request, Package $package): JsonResponse
    {
        if ($package->estado === 'entregado') {
            return response()->json(['error' => 'No se puede anular una encomienda entregada.'], 400);
        }

        if ($package->estado === 'anulado') {
            return response()->json(['error' => 'La encomienda ya está anulada.'], 400);
        }

        $package->update([
            'estado'      => 'anulado',
            'estado_pago' => 'anulado',
        ]);

        return response()->json(new PackageResource($package));
    }
}
