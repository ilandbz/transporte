<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PackageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'uuid_local'          => $this->uuid_local,
            'remitente_nombre'    => $this->remitente_nombre,
            'destinatario_nombre' => $this->destinatario_nombre,
            'descripcion'         => $this->descripcion,
            'peso_kg'             => $this->peso_kg,
            'cantidad_bultos'     => $this->cantidad_bultos,
            'precio'              => $this->precio,
            'estado_pago'         => $this->estado_pago,
            'estado'              => $this->estado,
            'qr_code'             => $this->qr_code,
            'sincronizado'        => $this->sincronizado,
            'emitido_en'          => $this->emitido_en?->toISOString(),
        ];
    }
}
