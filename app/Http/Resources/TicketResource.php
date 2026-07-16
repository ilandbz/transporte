<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                       => $this->id,
            'uuid_local'               => $this->uuid_local,
            'numero_asiento'           => $this->numero_asiento,
            'clase'                    => $this->clase,
            'estado'                   => $this->estado,
            'origen_tramo'             => $this->origen_tramo,
            'destino_tramo'            => $this->destino_tramo,
            'dni_pasajero'             => $this->dni_pasajero,
            'nombre_pasajero'          => $this->nombre_pasajero,
            'telefono_pasajero'        => $this->cliente?->telefono,
            'placa_vehiculo'           => $this->placa_vehiculo,
            'precio'                   => $this->precio,
            'metodo_pago'              => $this->metodo_pago,
            'tipo_documento'           => $this->tipo_documento,
            'numero_completo'          => $this->numero_completo,
            'serie_cpe'                => $this->serie_cpe,
            'correlativo_cpe'          => $this->correlativo_cpe,
            'cdr_status'               => $this->cdr_status,
            'sincronizado'             => $this->sincronizado,
            'emitido_en_contingencia'  => $this->emitido_en_contingencia,
            'emitido_en'               => $this->emitido_en?->toISOString(),
            'esta_emitido'             => $this->esta_emitido,
            'trip'                     => $this->when($this->relationLoaded('trip') && $this->trip, [
                'id'               => $this->trip?->id,
                'placa_vehiculo'   => $this->trip?->placa_vehiculo,
                'numero_manifiesto' => $this->trip?->numero_manifiesto,
            ]),
        ];
    }
}
