<?php

namespace App\Services;

use App\Models\CpeError;
use App\Models\Package;
use App\Models\Ticket;
use Greenter\Model\Client\Client;
use Greenter\Model\Company\Address;
use Greenter\Model\Company\Company;
use Greenter\Model\Despatch\Despatch;
use Greenter\Model\Despatch\DespatchDetail;
use Greenter\Model\Sale\Invoice;
use Greenter\Model\Sale\Legend;
use Greenter\Model\Sale\SaleDetail;
use Greenter\Model\Voided\Voided;
use Greenter\Model\Voided\VoidedDetail;
use Greenter\See;
use Greenter\Ws\Services\SunatEndpoints;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SunatGreenterService
{
    private See $see;

    public function __construct()
    {
        $this->see = $this->buildSee();
    }

    /**
     * Construye la instancia See con certificado y credenciales.
     */
    private function buildSee(): See
    {
        $see = new See();

        $certPath = base_path(config('greenter.cert_path'));
        $certPass = config('greenter.cert_pass');

        $see->setCertificate(file_get_contents($certPath));

        // Establecer servicio según ambiente
        $produccion = config('greenter.produccion', false);
        $see->setService(
            $produccion
                ? SunatEndpoints::FE_PRODUCCION
                : SunatEndpoints::FE_BETA
        );

        // Credenciales SOL
        $see->setClaveSOL(
            config('greenter.ruc'),
            config('greenter.sol_user'),
            config('greenter.sol_pass')
        );

        return $see;
    }

    /**
     * Obtiene el siguiente correlativo para una serie.
     * Usa bloqueo de BD para evitar duplicados en concurrencia.
     */
    private function getSiguienteCorrelativo(string $tipo): int
    {
        return DB::transaction(function () use ($tipo) {
            $serie = $tipo === 'BOLETA'
                ? config('greenter.serie_boleta')
                : config('greenter.serie_factura');

            $ultimo = Ticket::where('serie_cpe', $serie)
                ->lockForUpdate()
                ->max('correlativo_cpe');

            return ($ultimo ?? 0) + 1;
        });
    }

    /**
     * Construye el objeto Company (emisor) desde config.
     */
    private function buildCompany(): Company
    {
        $address = (new Address())
            ->setUbigueo(config('greenter.ubigeo'))
            ->setDepartamento(config('greenter.departamento'))
            ->setProvincia(config('greenter.provincia'))
            ->setDistrito(config('greenter.distrito'))
            ->setUrbanizacion('-')
            ->setDireccion(config('greenter.direccion'))
            ->setCodLocal(config('greenter.cod_local'));

        return (new Company())
            ->setRuc(config('greenter.ruc'))
            ->setRazonSocial(config('greenter.razon_social'))
            ->setNombreComercial(config('greenter.nombre_comercial'))
            ->setAddress($address);
    }

    /**
     * Convierte monto a palabras (simplificado para CPE).
     */
    private function montoEnLetras(float $monto): string
    {
        $entero = (int) floor($monto);
        $centavos = round(($monto - $entero) * 100);
        return "SON {$entero} CON {$centavos}/100 SOLES";
    }

    /**
     * Emite una Boleta Electrónica para un pasaje.
     * Catálogo N°19: incluye placa, ubigeos, datos del pasajero.
     */
    public function emitirBoleta(Ticket $ticket): array
    {
        try {
            $serie       = config('greenter.serie_boleta');
            $correlativo = $this->getSiguienteCorrelativo('BOLETA');
            $precio      = (float) $ticket->precio;
            $baseImponible = round($precio / 1.18, 2);
            $igv           = round($precio - $baseImponible, 2);

            // Cliente (facturación)
            $docFacturacion = $ticket->documento_facturacion ?? $ticket->dni_pasajero ?? '00000000';
            $tipDoc = strlen($docFacturacion) === 11 ? '6' : (strlen($docFacturacion) === 8 ? '1' : '0'); // 6=RUC, 1=DNI, 0=varios
            $client = (new Client())
                ->setTipoDoc($tipDoc)
                ->setNumDoc($docFacturacion)
                ->setRznSocial($ticket->nombre_facturacion ?? $ticket->nombre_pasajero ?? 'CLIENTE VARIOS');

            // Descripción del servicio (incluye datos Catálogo N°19)
            $descripcion = implode(' | ', array_filter([
                'SERVICIO DE TRANSPORTE',
                "{$ticket->origen_tramo} - {$ticket->destino_tramo}",
                $ticket->numero_asiento ? "ASIENTO N°{$ticket->numero_asiento}" : null,
                $ticket->placa_vehiculo ? "PLACA: {$ticket->placa_vehiculo}" : null,
                $ticket->trip?->numero_manifiesto ? "MANIF: {$ticket->trip->numero_manifiesto}" : null,
            ]));

            $detail = (new SaleDetail())
                ->setCodProducto('PASAJE')
                ->setUnidad('ZZ')               // ZZ = Servicio
                ->setCantidad(1)
                ->setDescripcion($descripcion)
                ->setMtoValorUnitario($baseImponible)
                ->setMtoValorVenta($baseImponible)
                ->setMtoBaseIgv($baseImponible)
                ->setPorcentajeIgv(18)
                ->setIgv($igv)
                ->setTipAfeIgv('10')            // Gravado - Op. Onerosa
                ->setMtoPrecioUnitario($precio);

            $invoice = (new Invoice())
                ->setUblVersion('2.1')
                ->setTipoOperacion('0101')      // Venta interna
                ->setTipoDoc('03')              // 03 = Boleta
                ->setSerie($serie)
                ->setCorrelativo((string) $correlativo)
                ->setFechaEmision(new \DateTime($ticket->emitido_en)) // hora REAL
                ->setTipoMoneda('PEN')
                ->setClient($client)
                ->setCompany($this->buildCompany())
                ->setMtoOperGravadas($baseImponible)
                ->setMtoIGV($igv)
                ->setValorVenta($baseImponible)
                ->setSubTotal($precio)
                ->setMtoImpVenta($precio)
                ->setDetails([$detail])
                ->setLegends([
                    (new Legend())
                        ->setCode('1000')
                        ->setValue($this->montoEnLetras($precio))
                ]);

            $result = $this->see->send($invoice);

            // Guardar XML firmado
            $xmlPath = storage_path("app/cpe/{$serie}-{$correlativo}.xml");
            @mkdir(dirname($xmlPath), 0755, true);
            file_put_contents($xmlPath, $this->see->getFactory()->getLastXml());

            if (!$result->isSuccess()) {
                Log::error("Greenter error conexión: " . $result->getError()->getMessage());
                return [
                    'status' => false,
                    'serie' => null,
                    'correlativo' => null,
                    'cdr' => null,
                    'error' => $result->getError()->getMessage()
                ];
            }

            $cdr  = $result->getCdrResponse();
            $code = (int) $cdr->getCode();

            Log::info("CPE {$serie}-{$correlativo}: CDR code={$code} — {$cdr->getDescription()}");

            return [
                'status'      => $code === 0,
                'serie'       => $serie,
                'correlativo' => $correlativo,
                'cdr'         => (string) $code,
                'descripcion' => $cdr->getDescription(),
                'notas'       => $cdr->getNotes(),
            ];
        } catch (\Exception $e) {
            Log::error("SunatGreenterService::emitirBoleta — " . $e->getMessage());
            return [
                'status' => false,
                'serie' => null,
                'correlativo' => null,
                'cdr' => null,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Emite una Factura Electrónica (cliente con RUC).
     */
    public function emitirFactura(Ticket $ticket): array
    {
        try {
            $serie       = config('greenter.serie_factura');
            $correlativo = $this->getSiguienteCorrelativo('FACTURA');
            $precio      = (float) $ticket->precio;
            $baseImponible = round($precio / 1.18, 2);
            $igv           = round($precio - $baseImponible, 2);

            // Cliente (facturación)
            $docFacturacion = $ticket->documento_facturacion ?? '00000000000';
            $client = (new Client())
                ->setTipoDoc('6') // 6 = RUC
                ->setNumDoc($docFacturacion)
                ->setRznSocial($ticket->nombre_facturacion ?? 'EMPRESA GENÉRICA');

            $descripcion = implode(' | ', array_filter([
                'SERVICIO DE TRANSPORTE',
                "{$ticket->origen_tramo} - {$ticket->destino_tramo}",
                $ticket->numero_asiento ? "ASIENTO N°{$ticket->numero_asiento}" : null,
                $ticket->placa_vehiculo ? "PLACA: {$ticket->placa_vehiculo}" : null,
            ]));

            $detail = (new SaleDetail())
                ->setCodProducto('PASAJE')
                ->setUnidad('ZZ')
                ->setCantidad(1)
                ->setDescripcion($descripcion)
                ->setMtoValorUnitario($baseImponible)
                ->setMtoValorVenta($baseImponible)
                ->setMtoBaseIgv($baseImponible)
                ->setPorcentajeIgv(18)
                ->setIgv($igv)
                ->setTipAfeIgv('10')
                ->setMtoPrecioUnitario($precio);

            $invoice = (new Invoice())
                ->setUblVersion('2.1')
                ->setTipoOperacion('0101')
                ->setTipoDoc('01')              // 01 = Factura
                ->setSerie($serie)
                ->setCorrelativo((string) $correlativo)
                ->setFechaEmision(new \DateTime($ticket->emitido_en))
                ->setTipoMoneda('PEN')
                ->setClient($client)
                ->setCompany($this->buildCompany())
                ->setMtoOperGravadas($baseImponible)
                ->setMtoIGV($igv)
                ->setValorVenta($baseImponible)
                ->setSubTotal($precio)
                ->setMtoImpVenta($precio)
                ->setDetails([$detail])
                ->setLegends([
                    (new Legend())
                        ->setCode('1000')
                        ->setValue($this->montoEnLetras($precio))
                ]);

            $result = $this->see->send($invoice);

            if (!$result->isSuccess()) {
                return [
                    'status' => false,
                    'serie' => null,
                    'correlativo' => null,
                    'cdr' => null,
                    'error' => $result->getError()->getMessage()
                ];
            }

            $cdr  = $result->getCdrResponse();
            $code = (int) $cdr->getCode();

            return [
                'status'      => $code === 0,
                'serie'       => $serie,
                'correlativo' => $correlativo,
                'cdr'         => (string) $code,
                'descripcion' => $cdr->getDescription(),
            ];
        } catch (\Exception $e) {
            Log::error("SunatGreenterService::emitirFactura — " . $e->getMessage());
            return [
                'status' => false,
                'serie' => null,
                'correlativo' => null,
                'cdr' => null,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Emite Guía de Remisión Electrónica (GRE) para encomiendas.
     * Tipo T001 — Transportista.
     */
    public function emitirGRE(Package $package): array
    {
        try {
            $serie       = config('greenter.serie_gre');
            $correlativo = DB::transaction(function () use ($serie) {
                $ultimo = Package::where('serie_cpe', $serie)->lockForUpdate()->max('correlativo_cpe');
                return ($ultimo ?? 0) + 1;
            });

            // TODO: implementar GRE completa con Greenter\Model\Despatch
            // Requiere: puntos de partida/llegada, destinatario, bienes transportados
            Log::info("GRE {$serie}-{$correlativo} para Package #{$package->id} — pendiente implementación completa");

            return [
                'status' => false,
                'serie' => null,
                'correlativo' => null,
                'cdr' => null,
                'error' => 'GRE pendiente de implementación completa'
            ];
        } catch (\Exception $e) {
            Log::error("SunatGreenterService::emitirGRE — " . $e->getMessage());
            return [
                'status' => false,
                'serie' => null,
                'correlativo' => null,
                'cdr' => null,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Prueba de conexión con SUNAT Beta.
     * Usar para verificar que el certificado y credenciales funcionan.
     */
    public function testConexion(): array
    {
        try {
            // Crear una boleta mínima de prueba
            $testInvoice = (new Invoice())
                ->setUblVersion('2.1')
                ->setTipoOperacion('0101')
                ->setTipoDoc('03')
                ->setSerie('B001')
                ->setCorrelativo('99999999')
                ->setFechaEmision(new \DateTime())
                ->setTipoMoneda('PEN')
                ->setClient(
                    (new Client())
                        ->setTipoDoc('0')
                        ->setNumDoc('-')
                        ->setRznSocial('TEST')
                )
                ->setCompany($this->buildCompany())
                ->setMtoOperGravadas(10.00)
                ->setMtoIGV(1.80)
                ->setValorVenta(10.00)
                ->setSubTotal(11.80)
                ->setMtoImpVenta(11.80)
                ->setDetails([
                    (new SaleDetail())
                        ->setCodProducto('TEST')
                        ->setUnidad('ZZ')
                        ->setCantidad(1)
                        ->setDescripcion('PRUEBA CONEXION')
                        ->setMtoValorUnitario(10.00)
                        ->setMtoValorVenta(10.00)
                        ->setMtoBaseIgv(10.00)
                        ->setPorcentajeIgv(18)
                        ->setIgv(1.80)
                        ->setTipAfeIgv('10')
                        ->setMtoPrecioUnitario(11.80)
                ])
                ->setLegends([
                    (new Legend())->setCode('1000')->setValue('SON ONCE CON 80/100 SOLES')
                ]);

            $result = $this->see->send($testInvoice);

            if (!$result->isSuccess()) {
                return [
                    'ok'      => false,
                    'mensaje' => 'Error de conexión: ' . $result->getError()->getMessage()
                ];
            }

            $cdr = $result->getCdrResponse();
            return [
                'ok'          => true,
                'cdr_code'    => $cdr->getCode(),
                'descripcion' => $cdr->getDescription(),
                'mensaje'     => 'Conexión con SUNAT Beta exitosa',
            ];
        } catch (\Exception $e) {
            return ['ok' => false, 'mensaje' => $e->getMessage()];
        }
    }

    /**
     * Anular un comprobante electrónico (Comunicación de Baja)
     */
    public function anularComprobante(Ticket $ticket, string $motivo): array
    {
        try {
            $tipoDoc = $ticket->tipo_documento === 'FACTURA' ? '01' : '03';

            // Usaremos el ID del ticket como correlativo diario para garantizar unicidad
            $correlativoBaja = str_pad((string)($ticket->id % 99999), 5, '0', STR_PAD_LEFT);

            $detail = (new VoidedDetail())
                ->setTipoDoc($tipoDoc)
                ->setSerie($ticket->serie_cpe)
                ->setCorrelativo((string) $ticket->correlativo_cpe)
                ->setDesMotivoBaja($motivo);

            $voided = (new Voided())
                ->setCorrelativo($correlativoBaja)
                ->setFecGeneracion(new \DateTime($ticket->emitido_en))
                ->setFecComunicacion(new \DateTime())
                ->setCompany($this->buildCompany())
                ->setDetails([$detail]);

            $result = $this->see->send($voided);

            // Guardar XML firmado de la baja
            $xmlPath = storage_path("app/cpe/BAJA-{$ticket->serie_cpe}-{$ticket->correlativo_cpe}.xml");
            @mkdir(dirname($xmlPath), 0755, true);
            file_put_contents($xmlPath, $this->see->getFactory()->getLastXml());

            if (!$result->isSuccess()) {
                Log::error("Greenter error conexión baja: " . $result->getError()->getMessage());
                return ['status' => false, 'cdr' => null, 'error' => $result->getError()->getMessage()];
            }

            $ticketCdr = $result->getCdrResponse();
            $code = (int) $ticketCdr->getCode();

            Log::info("BAJA {$ticket->serie_cpe}-{$ticket->correlativo_cpe}: CDR code={$code} — {$ticketCdr->getDescription()}");

            return [
                'status'      => $code === 0,
                'cdr'         => (string) $code,
                'descripcion' => $ticketCdr->getDescription(),
                'notas'       => $ticketCdr->getNotes(),
            ];
        } catch (\Exception $e) {
            Log::error("SunatGreenterService::anularComprobante — " . $e->getMessage());
            return ['status' => false, 'cdr' => null, 'error' => $e->getMessage()];
        }
    }
}
