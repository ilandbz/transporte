# SKILL.md — Backend Services (Laravel)
# Servicios de Lógica de Negocio

> Leer también: `SKILL.md` (raíz del proyecto) antes de usar este archivo.

---

## Servicios a implementar en `app/Services/`

---

### TicketService.php

Responsabilidades:
- Crear un ticket (pasaje) verificando disponibilidad de asiento
- Calcular tarifa desde `route_tariffs` por par origen-destino
- Manejar el estado del asiento en el manifiesto activo
- Emitir CPE si hay conexión, o marcar `tipo_documento = TICKET_INTERNO` si no

```php
// Firma del método principal
public function create(array $data, Trip $trip): Ticket

// $data debe incluir:
// uuid_local, numero_asiento, ubigeo_origen, ubigeo_destino,
// dni_pasajero, nombre_pasajero, emitido_en, tipo_documento,
// sincronizado, emitido_en_contingencia
```

---

### SyncService.php

Responsabilidades:
- Recibir array de tickets desde app móvil (lote JSON)
- Detectar duplicados por `uuid_local` (idempotente)
- Despachar `SyncBatchJob` para procesamiento en cola
- Retornar resumen: `{ procesados, duplicados, errores }`

```php
public function processBatch(array $tickets, User $driver): array

// Regla crítica: usar $ticket['emitido_en'] como fecha del CPE,
// NUNCA now() o el timestamp de la petición HTTP.
```

---

### SunatGreenterService.php

Responsabilidades:
- Construir el payload de Greenter para Boleta/Factura de transporte
- Firmar con certificado `.pfx` (ruta en `.env`: `GREENTER_CERT_PATH`)
- Enviar a SUNAT y parsear CDR
- Guardar estado: `serie`, `correlativo`, `cdr_status`, `hash_cpe`

```php
public function emitirBoleta(Ticket $ticket): array  // retorna ['status', 'serie', 'correlativo', 'cdr']
public function emitirFactura(Ticket $ticket): array
public function emitirGRE(Package $package): array   // Guía de Remisión Transportista
```

Variables de entorno requeridas:
```
GREENTER_RUC=
GREENTER_CERT_PATH=
GREENTER_CERT_PASS=
GREENTER_SOL_USER=
GREENTER_SOL_PASS=
GREENTER_PRODUCCION=false
```

Campos obligatorios Catálogo N°19 en el payload:
- `placa_vehiculo` — del Trip relacionado
- `ubigeo_origen` / `ubigeo_destino` — 6 dígitos
- `direccion_origen` / `direccion_destino`
- `numero_manifiesto` — del Trip (MTC)

---

### DniRucApiService.php

Responsabilidades:
- Consultar DNI vía API (apis.net.pe o similar)
- Consultar RUC vía SUNAT
- Cachear resultado 24h en Redis/database (`dni_cache`)
- Retornar `null` si no hay conexión (app continúa sin bloquear)

```php
public function consultarDni(string $dni): ?array   // ['nombre', 'apellidos']
public function consultarRuc(string $ruc): ?array   // ['razon_social', 'direccion']
```

Variables de entorno:
```
DNI_API_URL=
DNI_API_TOKEN=
```

---

### GpsTrackingService.php

Responsabilidades:
- Recibir coordenadas desde la app móvil
- Almacenar en tabla `gps_tracks` (trip_id, lat, lng, speed, timestamp)
- Mantener último punto en caché Redis para el dashboard

```php
public function store(Trip $trip, float $lat, float $lng, ?float $speed = null): void
public function getLastPosition(Trip $trip): ?array
```

---

## Jobs en `app/Jobs/`

### SyncBatchJob.php
- Se despacha desde `SyncService::processBatch()`
- Procesa cada ticket del lote en secuencia
- Llama a `SunatGreenterService` por cada uno
- Actualiza `sincronizado = true` y `tipo_documento` correcto
- En caso de error SUNAT: guarda en tabla `cpe_errors` para reintentos

---

## Endpoints API (routes/api.php)

```php
// Prefijo: /api/v1  |  Middleware: auth:sanctum

// --- Viajes ---
POST   /trips                      // Abrir manifiesto
PATCH  /trips/{trip}/close         // Cerrar manifiesto
GET    /trips/{trip}/seats         // Estado asientos

// --- Tickets ---
POST   /tickets                    // Vender pasaje (online)
GET    /tickets/{ticket}

// --- Encomiendas ---
POST   /packages                   // Registrar encomienda
GET    /packages/{package}/qr      // QR para escaneo
PATCH  /packages/{package}/deliver // Marcar entregada

// --- Sincronización ---
POST   /sync/batch                 // Lote desde modo offline

// --- GPS ---
POST   /trips/{trip}/gps           // Track de posición

// --- Identidad ---
GET    /consulta/dni/{dni}
GET    /consulta/ruc/{ruc}
```
