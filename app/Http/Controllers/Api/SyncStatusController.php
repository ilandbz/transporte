<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CpeError;
use App\Models\Package;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SyncStatusController extends Controller
{
    /**
     * GET /api/v1/sync/status
     */
    public function status(Request $request): JsonResponse
    {
        $userId = auth()->id();

        // Pendientes
        $ticketsPendientes = Ticket::pendienteSync()->where('user_id', $userId)->count();
        $packagesPendientes = Package::pendienteSync()->where('user_id', $userId)->count();
        $pendientes = $ticketsPendientes + $packagesPendientes;

        // Errores
        $conError = CpeError::pendiente()
            ->whereHasMorph('documento', [Ticket::class, Package::class], function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })->count();

        // Sincronizados hoy
        $ticketsSincronizadosHoy = Ticket::where('sincronizado', true)
            ->whereDate('sincronizado_en', today())
            ->where('user_id', $userId)
            ->count();

        $packagesSincronizadosHoy = Package::where('sincronizado', true)
            ->whereDate('sincronizado_en', today())
            ->where('user_id', $userId)
            ->count();

        $sincronizadosHoy = $ticketsSincronizadosHoy + $packagesSincronizadosHoy;

        return response()->json([
            'pendientes'        => $pendientes,
            'con_error'         => $conError,
            'sincronizados_hoy' => $sincronizadosHoy,
        ]);
    }

    /**
     * GET /api/v1/sync/errors
     */
    public function errors(Request $request): JsonResponse
    {
        $userId = auth()->id();

        $errores = CpeError::pendiente()
            ->with('documento')
            ->whereHasMorph('documento', [Ticket::class, Package::class], function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($errores);
    }
}
