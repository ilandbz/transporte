# SKILL.md — Facturación Electrónica SUNAT
# Greenter · Sistemas del Contribuyente · Catálogo N°19

> Leer también: `SKILL.md` (raíz del proyecto) antes de usar este archivo.

---

## Modalidad: Sistemas del Contribuyente

La empresa emite CPEs directamente desde el sistema.
NO se usa OSE ni proveedor intermediario.
El sistema firma y envía directamente a los WebServices de SUNAT.

---

## Instalación Greenter

```bash
composer require greenter/greenter
```

Requiere extensión PHP: `soap`, `openssl`, `zlib`

---

## Variables de Entorno (.env)

```env
GREENTER_RUC=20123456789
GREENTER_RAZON_SOCIAL="ASOC. TRANSPORTISTAS SHINHUA DE PUÑOS"
GREENTER_DIRECCION="JR. PRINCIPAL 123, PUÑOS, HUÁNUCO"
GREENTER_UBIGEO=100801

GREENTER_CERT_PATH=/var/www/transporte/certs/certificado.pfx
GREENTER_CERT_PASS=clave_certificado

GREENTER_SOL_USER=20123456789SUNAT
GREENTER_SOL_PASS=contraseña_sol

# false = Beta SUNAT, true = Producción
GREENTER_PRODUCCION=false

# Series por tipo
GREENTER_SERIE_BOLETA=B001
GREENTER_SERIE_FACTURA=F001
GREENTER_SERIE_GRE=T001
```

---

## Estructura del Payload — Boleta de Transporte

Campos obligatorios del **Catálogo N°19** para transporte terrestre:

```php
use Greenter\Model\Sale\Invoice;
use Greenter\Model\Sale\SaleDetail;
use Greenter\Model\Sale\Legend;
use Greenter\Model\Client\Client;
use Greenter\Model\Company\Company;
use Greenter\Model\Company\Address;

// En SunatGreenterService::emitirBoleta(Ticket $ticket)

$invoice = (new Invoice())
    ->setUblVersion('2.1')
    ->setTipoOperacion('0101')              // Venta interna
    ->setTipoDoc('03')                      // 03 = Boleta, 01 = Factura
    ->setSerie($serie)                      // B001
    ->setCorrelativo($correlativo)
    ->setFechaEmision(new \DateTime($ticket->emitido_en)) // ← hora REAL, no now()
    ->setTipoMoneda('PEN')
    ->setClient(
        (new Client())
            ->setTipoDoc('1')               // 1=DNI, 6=RUC
            ->setNumDoc($ticket->dni_pasajero ?? '00000000')
            ->setRznSocial($ticket->nombre_pasajero ?? 'CLIENTE VARIOS')
    )
    ->setCompany(
        (new Company())
            ->setRuc(config('greenter.ruc'))
            ->setRazonSocial(config('greenter.razon_social'))
            ->setNombreComercial('SHINHUA TRANSPORTES')
            ->setAddress((new Address())->setUbigueo(config('greenter.ubigeo')))
    )
    ->setDetails([
        (new SaleDetail())
            ->setCodProducto('PASAJE')
            ->setUnidad('ZZ')               // ZZ = Servicio
            ->setCantidad(1)
            ->setDescripcion(
                "PASAJE TERRESTRE: {$ticket->origen_tramo} - {$ticket->destino_tramo} | " .
                "ASIENTO N°{$ticket->numero_asiento} | " .
                "PLACA: {$ticket->placa_vehiculo}"
            )
            ->setMtoValorUnitario($ticket->precio / 1.18)
            ->setMtoValorVenta($ticket->precio / 1.18)
            ->setMtoBaseIgv($ticket->precio / 1.18)
            ->setPorcentajeIgv(18)
            ->setIgv($ticket->precio - ($ticket->precio / 1.18))
            ->setTipAfeIgv('10')            // Gravado
            ->setMtoPrecioUnitario($ticket->precio)
    ])
    // Datos adicionales transporte (Catálogo N°19)
    ->setGuias([])                          // Si aplica guía de remisión
    ->setLegends([
        (new Legend())
            ->setCode('1000')
            ->setValue('SON ' . strtoupper(numToWords($ticket->precio)) . ' SOLES')
    ]);
```

---

## Campos Adicionales Transporte (SUNAT UBL 2.1)

Para CPEs de transporte, agregar en el XML vía `setAdditionalData`:

```xml
<!-- Datos del transporte terrestre -->
<cac:Shipment>
  <cac:ShipmentStage>
    <cbc:TransportModeCode listID="UN/ECE 19">3</cbc:TransportModeCode>
  </cac:ShipmentStage>
  <cac:TransportHandlingUnit>
    <cac:TransportEquipment>
      <cbc:ID>{PLACA_VEHICULO}</cbc:ID>
    </cac:TransportEquipment>
  </cac:TransportHandlingUnit>
  <cac:OriginAddress>
    <cbc:CitySubdivisionName>{NOMBRE_ORIGEN}</cbc:CitySubdivisionName>
    <cbc:StreetName>{DIRECCION_ORIGEN}</cbc:StreetName>
    <cac:Country><cbc:IdentificationCode>PE</cbc:IdentificationCode></cac:Country>
  </cac:OriginAddress>
  <cac:Delivery>
    <cac:DeliveryAddress>
      <cbc:CitySubdivisionName>{NOMBRE_DESTINO}</cbc:CitySubdivisionName>
      <cbc:StreetName>{DIRECCION_DESTINO}</cbc:StreetName>
    </cac:DeliveryAddress>
  </cac:Delivery>
</cac:Shipment>
```

---

## Flujo Completo de Emisión

```
1. SunatGreenterService::emitirBoleta(Ticket $ticket)
     │
     ├── Obtener siguiente correlativo (con DB::transaction + lock)
     ├── Construir Invoice object
     ├── See::sign(invoice) con certificado .pfx
     ├── POST a WebService SUNAT
     │
     ├── ÉXITO (CDR código 0):
     │     ├── ticket->serie_cpe = 'B001'
     │     ├── ticket->correlativo_cpe = N
     │     ├── ticket->cdr_status = '0'
     │     ├── ticket->tipo_documento = 'BOLETA'
     │     └── ticket->save()
     │
     └── ERROR:
           ├── Guardar en cpe_errors
           └── Lanzar evento CpeRejectedEvent (para alerta en dashboard)
```

---

## Guía de Remisión Electrónica (GRE) — Encomiendas

```php
use Greenter\Model\Despatch\Despatch;

// En SunatGreenterService::emitirGRE(Package $package)
// Tipo: T001 (GRE Transportista)
// Catálogo: Remitente = empresa transportista
// Destinatario = cliente final de la encomienda
```

La GRE es para encomiendas, NO para pasajes.
Activar solo cuando `$package->tipo_documento` requiera GRE.

---

## Ambientes SUNAT

| Ambiente | URL Beta | URL Producción |
|---|---|---|
| Boleta/Factura | `https://e-beta.sunat.gob.pe/...` | `https://e-factura.sunat.gob.pe/...` |
| GRE | `https://e-beta.sunat.gob.pe/...` | `https://e-guiaremision.sunat.gob.pe/...` |

Greenter maneja las URLs automáticamente según `setService()`.

---

## Regularización de TICKET_INTERNO

Cuando `SyncService` procesa un lote de contingencia:

1. La fecha del CPE = `$ticket['emitido_en']` (hora real de venta en ruta)
2. NUNCA usar `now()` como fecha de emisión — SUNAT puede rechazarlo
3. SUNAT permite emitir CPEs con fecha hasta 7 días anteriores
4. Si el ticket tiene más de 7 días → emitir igual pero anotar en `cdr_descripcion`
