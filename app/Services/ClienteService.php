<?php

namespace App\Services;

use App\Models\Client;

class ClienteService
{
    public function __construct(private DniRucApiService $dniService) {}

    /**
     * Busca un cliente por documento; si no existe, intenta autocompletar
     * el nombre vía RENIEC/SUNAT y lo crea para reutilizarlo en próximas
     * ventas. Si no se pudo autocompletar y no vino $nombre, se crea igual
     * con el nombre que se le pase (puede quedar null temporalmente).
     * Si viene $telefono y el cliente no tenía uno guardado, se completa.
     */
    public function resolver(?string $documento, ?string $nombre, ?string $telefono = null): ?Client
    {
        if (empty($documento)) {
            return null;
        }

        $cliente = Client::where('documento', $documento)->first();
        if ($cliente) {
            $cambios = [];
            if (empty($cliente->nombre) && !empty($nombre)) {
                $cambios['nombre'] = $nombre;
            }
            if (empty($cliente->telefono) && !empty($telefono)) {
                $cambios['telefono'] = $telefono;
            }
            if ($cambios) {
                $cliente->update($cambios);
            }
            return $cliente;
        }

        $tipoDocumento = strlen($documento) === 11 ? 'RUC' : 'DNI';

        if (empty($nombre)) {
            $resultado = $tipoDocumento === 'RUC'
                ? $this->dniService->consultarRuc($documento)
                : $this->dniService->consultarDni($documento);

            if ($resultado) {
                $nombre = $tipoDocumento === 'RUC'
                    ? ($resultado['razon_social'] ?? $resultado['nombre'] ?? null)
                    : trim(($resultado['nombre'] ?? '') . ' ' . ($resultado['apellidos'] ?? ''));
            }
        }

        return Client::create([
            'documento'      => $documento,
            'tipo_documento' => $tipoDocumento,
            'nombre'         => $nombre ?: 'CLIENTES VARIOS',
            'telefono'       => $telefono,
        ]);
    }
}
