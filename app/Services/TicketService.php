<?php

namespace App\Services;

use App\Models\RouteTariff;
use App\Models\Ticket;
use App\Models\Trip;
use Illuminate\Support\Facades\DB;

class TicketService
{
    public function __construct(
        private SunatGreenterService $greenter,
        private DniRucApiService $dniService,
    ) {}

    public function create(array $data, Trip $trip): Ticket
    {
        // Verificar asiento disponible
        if ($trip->isAsientoOcupado($data['numero_asiento'])) {
            throw new \Exception("Asiento {$data['numero_asiento']} ya está ocupado.");
        }

        // Calcular precio si no viene en el payload
        if (empty($data['precio'])) {
            $tarifa = RouteTariff::where('route_id', $trip->route_id)
                ->where('origen_tramo', $data['origen_tramo'])
                ->where('destino_tramo', $data['destino_tramo'])
                ->where('clase', $data['clase'])
                ->first();

            if (!$tarifa) {
                throw new \Exception("No existe tarifa para {$data['origen_tramo']} → {$data['destino_tramo']}.");
            }

            $data['precio'] = $tarifa->precio;
        }

        // Consultar nombre si hay DNI y no vino
        if (!empty($data['dni_pasajero']) && empty($data['nombre_pasajero'])) {
            $persona = $this->dniService->consultarDni($data['dni_pasajero']);
            if ($persona) {
                $data['nombre_pasajero'] = trim(($persona['nombre'] ?? '') . ' ' . ($persona['apellidos'] ?? ''));
            }
        }

        return DB::transaction(function () use ($data, $trip) {
            $ticket = Ticket::create([
                'uuid_local'              => $data['uuid_local'],
                'trip_id'                 => $trip->id,
                'user_id'                 => auth()->id(),
                'numero_asiento'          => $data['numero_asiento'],
                'clase'                   => $data['clase'],
                'origen_tramo'            => $data['origen_tramo'],
                'destino_tramo'           => $data['destino_tramo'],
                'ubigeo_origen'           => $data['ubigeo_origen'],
                'ubigeo_destino'          => $data['ubigeo_destino'],
                'dni_pasajero'            => $data['dni_pasajero'] ?? null,
                'nombre_pasajero'         => $data['nombre_pasajero'] ?? null,
                'placa_vehiculo'          => $trip->placa_vehiculo,
                'precio'                  => $data['precio'],
                'estado_pago'             => $data['estado_pago'] ?? 'pendiente',
                'estado'                  => $data['estado'] ?? 'confirmado',
                'metodo_pago'             => $data['metodo_pago'],
                'tipo_documento'          => $data['tipo_documento'],
                'documento_facturacion'   => $data['documento_facturacion'] ?? null,
                'nombre_facturacion'      => $data['nombre_facturacion'] ?? null,
                'sincronizado'            => false,
                'emitido_en_contingencia' => $data['emitido_en_contingencia'],
                'emitido_en'              => $data['emitido_en'],
            ]);

            $trip->ocuparAsiento($data['numero_asiento']);

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

    public function calcularPrecio(Trip $trip, string $origen, string $destino, string $clase = 'normal'): float
    {
        $tarifa = RouteTariff::where('route_id', $trip->route_id)
            ->where('origen_tramo', $origen)
            ->where('destino_tramo', $destino)
            ->where('clase', $clase)
            ->firstOrFail();

        return (float) $tarifa->precio;
    }

    public function isAsientoDisponible(Trip $trip, int $asiento): bool
    {
        return !$trip->isAsientoOcupado($asiento);
    }
}
