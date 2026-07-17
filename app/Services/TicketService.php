<?php

namespace App\Services;

use App\Models\Lugar;
use App\Models\Ticket;
use App\Models\Trip;
use Illuminate\Support\Facades\DB;

class TicketService
{
    public function __construct(
        private SunatGreenterService $greenter,
        private ClienteService $clienteService,
    ) {}

    /**
     * Crea un ticket "simplificado": sin viaje/asiento/tarifa obligatorios.
     * El origen/destino se resuelven desde el catálogo de Lugares y el
     * precio lo ingresa directamente quien vende (conductor/counter/admin).
     */
    public function create(array $data): Ticket
    {
        $cliente = $this->clienteService->resolver(
            $data['dni_pasajero'] ?? null,
            $data['nombre_pasajero'] ?? null,
            $data['telefono_pasajero'] ?? null,
        );

        $origenLugar = Lugar::find($data['lugar_origen_id']);
        $destinoLugar = Lugar::find($data['lugar_destino_id']);

        $trip = !empty($data['trip_id']) ? Trip::find($data['trip_id']) : null;

        return DB::transaction(function () use ($data, $cliente, $origenLugar, $destinoLugar, $trip) {
            $ticket = Ticket::create([
                'uuid_local'              => $data['uuid_local'],
                'trip_id'                 => $trip?->id,
                'user_id'                 => auth()->id(),
                'branch_id'               => auth()->user()->branch_id,
                'cliente_id'              => $cliente?->id,
                'vehicle_id'              => $data['vehicle_id'] ?? null,
                'lugar_origen_id'         => $origenLugar?->id,
                'lugar_destino_id'        => $destinoLugar?->id,
                'ida_vuelta'              => $data['ida_vuelta'] ?? false,
                'concepto'                => $data['concepto'] ?? null,
                'origen_tramo'            => $origenLugar?->nombre,
                'destino_tramo'           => $destinoLugar?->nombre,
                'dni_pasajero'            => $cliente?->documento,
                'nombre_pasajero'         => $cliente?->nombre,
                'placa_vehiculo'          => $data['placa_vehiculo'] ?? null,
                'precio'                  => $data['precio'],
                'estado_pago'             => $data['estado_pago'] ?? 'pagado',
                'estado'                  => 'confirmado',
                'metodo_pago'             => $data['metodo_pago'],
                'tipo_documento'          => $data['tipo_documento'],
                'documento_facturacion'   => $cliente?->tipo_documento === 'RUC' ? $cliente->documento : null,
                'nombre_facturacion'      => $cliente?->tipo_documento === 'RUC' ? $cliente->nombre : null,
                'sincronizado'            => false,
                'emitido_en_contingencia' => $data['emitido_en_contingencia'] ?? false,
                'emitido_en'              => $data['emitido_en'],
            ]);

            // Intentar CPE si no es contingencia
            if (in_array($ticket->tipo_documento, ['BOLETA', 'FACTURA']) && !$ticket->emitido_en_contingencia) {
                try {
                    $res = $ticket->tipo_documento === 'BOLETA'
                        ? $this->greenter->emitirBoleta($ticket)
                        : $this->greenter->emitirFactura($ticket);

                    if ($res['status']) {
                        $ticket->update([
                            'serie_cpe'       => $res['serie'],
                            'correlativo_cpe' => $res['correlativo'],
                            'cdr_status'      => $res['cdr'],
                            'sincronizado'    => true,
                            'sincronizado_en' => now(),
                        ]);
                    }
                } catch (\Exception $e) {
                    $ticket->update(['tipo_documento' => 'TICKET_INTERNO']);
                }
            }

            return $ticket->fresh();
        });
    }
}
