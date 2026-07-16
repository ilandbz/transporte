<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PackageResource;
use App\Models\Lugar;
use App\Models\Package;
use App\Services\ClienteService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PackageController extends Controller
{
    public function __construct(private ClienteService $clienteService) {}

    // POST /api/v1/packages — Registrar encomienda (simplificado: sin viaje
    // obligatorio, origen/destino desde el catálogo de Lugares, clientes
    // reutilizables por DNI/RUC)
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'uuid_local'             => 'required|uuid',
            'lugar_origen_id'        => 'required|exists:lugars,id',
            'lugar_destino_id'       => 'required|exists:lugars,id|different:lugar_origen_id',
            'remitente_documento'    => 'nullable|string|max:15',
            'remitente_nombre'       => 'nullable|string|max:200',
            'remitente_telefono'     => 'nullable|string|max:15',
            'destinatario_documento' => 'nullable|string|max:15',
            'destinatario_nombre'    => 'nullable|string|max:200',
            'destinatario_telefono'  => 'nullable|string|max:15',
            'descripcion'            => 'required|string|max:500',
            'peso_kg'                => 'nullable|numeric|min:0.1',
            'cantidad_bultos'        => 'required|integer|min:1',
            'precio'                 => 'required|numeric|min:0',
            'estado_pago'            => 'required|in:pagado,por_cobrar',
            'vehicle_id'             => 'nullable|exists:vehicles,id',
            'emitido_en'             => 'required|date',
        ]);

        $clienteRemitente = $this->clienteService->resolver(
            $validated['remitente_documento'] ?? null,
            $validated['remitente_nombre'] ?? null,
        );
        $clienteDestinatario = $this->clienteService->resolver(
            $validated['destinatario_documento'] ?? null,
            $validated['destinatario_nombre'] ?? null,
        );

        $origenLugar = Lugar::find($validated['lugar_origen_id']);
        $destinoLugar = Lugar::find($validated['lugar_destino_id']);

        $package = Package::create([
            'uuid_local'              => $validated['uuid_local'],
            'user_id'                 => auth()->id(),
            'branch_id'               => auth()->user()->branch_id,
            'cliente_remitente_id'    => $clienteRemitente?->id,
            'cliente_destinatario_id' => $clienteDestinatario?->id,
            'vehicle_id'              => $validated['vehicle_id'] ?? null,
            'lugar_origen_id'         => $origenLugar?->id,
            'lugar_destino_id'        => $destinoLugar?->id,
            'remitente_nombre'        => $clienteRemitente?->nombre ?? $validated['remitente_nombre'] ?? null,
            'remitente_dni'           => $clienteRemitente?->documento ?? $validated['remitente_documento'] ?? null,
            'remitente_telefono'      => $validated['remitente_telefono'] ?? null,
            'destinatario_nombre'     => $clienteDestinatario?->nombre ?? $validated['destinatario_nombre'] ?? null,
            'destinatario_dni'        => $clienteDestinatario?->documento ?? $validated['destinatario_documento'] ?? null,
            'destinatario_telefono'   => $validated['destinatario_telefono'] ?? null,
            'descripcion'             => $validated['descripcion'],
            'peso_kg'                 => $validated['peso_kg'] ?? null,
            'cantidad_bultos'         => $validated['cantidad_bultos'],
            'precio'                  => $validated['precio'],
            'estado_pago'             => $validated['estado_pago'],
            'estado'                  => 'en_transito',
            'qr_code'                 => hash('sha256', $validated['uuid_local']),
            'sincronizado'            => false,
            'emitido_en_contingencia' => false,
            'tipo_documento'          => 'TICKET_INTERNO',
            'emitido_en'              => $validated['emitido_en'],
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
        if ($package->user_id !== auth()->id() && auth()->user()->role !== 'admin') {
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
