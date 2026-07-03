<?php

namespace App\Services;

use App\Jobs\SyncBatchJob;
use App\Models\Ticket;
use App\Models\User;

class SyncService
{
    /**
     * Procesa lote de tickets desde app móvil (modo offline).
     * Idempotente: detecta duplicados por uuid_local.
     * NUNCA usa now() como fecha — siempre $item['emitido_en'].
     */
    public function processBatch(array $tickets, User $driver): array
    {
        $procesados = 0;
        $duplicados = 0;
        $errores    = 0;
        $detalle    = [];

        foreach ($tickets as $item) {
            // Idempotencia: saltar si ya existe
            if (Ticket::where('uuid_local', $item['uuid_local'])->exists()) {
                $duplicados++;
                $detalle[] = ['uuid_local' => $item['uuid_local'], 'estado' => 'duplicado'];
                continue;
            }

            try {
                $ticket = Ticket::create([
                    'uuid_local'              => $item['uuid_local'],
                    'trip_id'                 => $item['trip_id'] ?? null,
                    'user_id'                 => $driver->id,
                    'numero_asiento'          => $item['numero_asiento'],
                    'origen_tramo'            => $item['origen_tramo'],
                    'destino_tramo'           => $item['destino_tramo'],
                    'ubigeo_origen'           => $item['ubigeo_origen'],
                    'ubigeo_destino'          => $item['ubigeo_destino'],
                    'dni_pasajero'            => $item['dni_pasajero'] ?? null,
                    'nombre_pasajero'         => $item['nombre_pasajero'] ?? null,
                    'placa_vehiculo'          => $item['placa_vehiculo'] ?? '',
                    'precio'                  => $item['precio'],
                    'metodo_pago'             => $item['metodo_pago'],
                    'tipo_documento'          => 'TICKET_INTERNO',
                    'sincronizado'            => false,
                    'emitido_en_contingencia' => true,
                    'emitido_en'              => $item['emitido_en'], // ← hora real, NO now()
                ]);

                SyncBatchJob::dispatch($ticket);

                $procesados++;
                $detalle[] = ['uuid_local' => $item['uuid_local'], 'estado' => 'en_proceso', 'ticket_id' => $ticket->id];
            } catch (\Exception $e) {
                $errores++;
                $detalle[] = ['uuid_local' => $item['uuid_local'], 'estado' => 'error', 'mensaje' => $e->getMessage()];
            }
        }

        return compact('procesados', 'duplicados', 'errores', 'detalle');
    }
}
