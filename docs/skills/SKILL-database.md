# SKILL.md — Base de Datos
# Esquema de Migraciones y Relaciones Eloquent

> Leer también: `SKILL.md` (raíz del proyecto) antes de usar este archivo.

---

## IMPORTANTE: Migraciones ya existentes

> NO ejecutar `php artisan make:migration` para estas tablas. YA EXISTEN.

```
database/migrations/
├── 0001_01_01_000000_create_users_table.php       ← Laravel default
├── 0001_01_01_000001_create_cache_table.php       ← Laravel default
├── 0001_01_01_000002_create_jobs_table.php        ← Laravel default
├── 2024_01_01_000000_create_passkeys_table.php
├── 2025_08_14_170933_add_two_factor_columns_to_users_table.php
├── 2026_06_26_011043_create_routes_table.php      ← EXISTE
├── 2026_06_26_011046_create_route_tariffs_table.php ← EXISTE
├── 2026_06_26_011119_create_vehicles_table.php    ← EXISTE
├── 2026_06_26_011137_create_trips_table.php       ← EXISTE
├── 2026_06_26_011148_create_tickets_table.php     ← EXISTE
└── 2026_06_26_011158_create_packages_table.php    ← EXISTE
```

---

## Esquema Esperado de cada Tabla

### `routes` — Rutas y paradas
```php
$table->id();
$table->string('nombre');                    // 'Huánuco - Puños'
$table->string('origen');                    // 'Huánuco'
$table->string('destino');                   // 'Puños'
$table->string('ubigeo_origen', 6);
$table->string('ubigeo_destino', 6);
$table->json('paradas')->nullable();         // ['Llata', 'Chiquián']
$table->boolean('activo')->default(true);
$table->timestamps();
```

### `route_tariffs` — Matriz de precios por tramo
```php
$table->id();
$table->foreignId('route_id')->constrained();
$table->string('origen_tramo');              // 'Huánuco'
$table->string('destino_tramo');             // 'Llata'
$table->string('ubigeo_origen', 6);
$table->string('ubigeo_destino', 6);
$table->decimal('precio', 8, 2);
$table->string('clase')->default('normal'); // normal, vip
$table->timestamps();
// unique: ['route_id', 'origen_tramo', 'destino_tramo', 'clase']
```

### `vehicles` — Flota de vehículos
```php
$table->id();
$table->string('placa', 10)->unique();
$table->string('marca')->nullable();
$table->string('modelo')->nullable();
$table->enum('tipo', ['minivan', 'bus', 'coaster']);
$table->integer('capacidad_asientos');
$table->json('layout_asientos')->nullable(); // config visual del mapa
$table->boolean('activo')->default(true);
$table->timestamps();
```

### `trips` — Manifiesto de viaje
```php
$table->id();
$table->foreignId('route_id')->constrained();
$table->foreignId('vehicle_id')->constrained();
$table->foreignId('user_id')->constrained();     // conductor
$table->string('placa_vehiculo', 10);            // desnormalizado para CPE
$table->string('numero_manifiesto')->nullable();  // MTC
$table->dateTime('fecha_salida');
$table->dateTime('fecha_llegada_estimada')->nullable();
$table->enum('estado', ['abierto', 'en_ruta', 'cerrado'])->default('abierto');
$table->json('asientos_ocupados')->default('[]');
$table->decimal('lat_inicio', 10, 7)->nullable();
$table->decimal('lng_inicio', 10, 7)->nullable();
$table->timestamps();
```

### `tickets` — Pasajes vendidos
```php
$table->id();
$table->uuid('uuid_local')->unique();
$table->foreignId('trip_id')->constrained();
$table->foreignId('user_id')->constrained();     // quien vendió
$table->integer('numero_asiento');
$table->string('origen_tramo');
$table->string('destino_tramo');
$table->string('ubigeo_origen', 6);
$table->string('ubigeo_destino', 6);
$table->string('dni_pasajero', 15)->nullable();
$table->string('nombre_pasajero')->nullable();
$table->string('placa_vehiculo', 10);            // desnormalizado para SUNAT
$table->decimal('precio', 8, 2);
$table->enum('metodo_pago', ['efectivo', 'yape', 'plin', 'transferencia'])->default('efectivo');

// Facturación electrónica
$table->enum('tipo_documento', ['BOLETA', 'FACTURA', 'TICKET_INTERNO'])->default('TICKET_INTERNO');
$table->string('serie_cpe', 4)->nullable();       // B001, F001
$table->integer('correlativo_cpe')->nullable();
$table->string('hash_cpe')->nullable();
$table->string('cdr_status')->nullable();         // 0 = aceptado
$table->text('cdr_descripcion')->nullable();

// Auditoría asíncrona
$table->boolean('sincronizado')->default(false);
$table->boolean('emitido_en_contingencia')->default(false);
$table->timestamp('emitido_en')->nullable();      // fecha real de venta
$table->timestamp('sincronizado_en')->nullable();
$table->timestamps();
```

### `packages` — Encomiendas
```php
$table->id();
$table->uuid('uuid_local')->unique();
$table->foreignId('trip_id')->constrained();
$table->foreignId('user_id')->constrained();

// Remitente
$table->string('remitente_nombre');
$table->string('remitente_dni', 15)->nullable();
$table->string('remitente_telefono')->nullable();

// Destinatario
$table->string('destinatario_nombre');
$table->string('destinatario_dni', 15)->nullable();
$table->string('destinatario_telefono')->nullable();

// Carga
$table->string('descripcion');
$table->decimal('peso_kg', 8, 2)->nullable();
$table->integer('cantidad_bultos')->default(1);
$table->decimal('precio', 8, 2);
$table->string('qr_code')->unique();             // generado en app
$table->enum('estado', ['pendiente', 'en_transito', 'entregado'])->default('pendiente');
$table->enum('estado_pago', ['pagado', 'por_cobrar'])->default('pagado');
$table->timestamp('entregado_en')->nullable();

// Auditoría asíncrona (igual que tickets)
$table->enum('tipo_documento', ['BOLETA', 'FACTURA', 'TICKET_INTERNO'])->default('TICKET_INTERNO');
$table->boolean('sincronizado')->default(false);
$table->boolean('emitido_en_contingencia')->default(false);
$table->timestamp('emitido_en')->nullable();
$table->timestamp('sincronizado_en')->nullable();
$table->timestamps();
```

---

## Tablas Adicionales a Crear

Usar `php artisan make:migration create_XXX_table`:

```
create_gps_tracks_table       ← tracking en tiempo real
create_cpe_errors_table       ← errores SUNAT para reintento
create_dni_cache_table        ← caché de consultas DNI/RUC
```

### `gps_tracks`
```php
$table->id();
$table->foreignId('trip_id')->constrained();
$table->decimal('lat', 10, 7);
$table->decimal('lng', 10, 7);
$table->decimal('velocidad_kmh', 6, 2)->nullable();
$table->timestamp('registrado_en');
// NO usar timestamps() aquí, solo registrado_en para eficiencia
```

### `cpe_errors`
```php
$table->id();
$table->morphs('documento');           // ticket_id o package_id
$table->text('error_mensaje');
$table->json('payload_enviado')->nullable();
$table->integer('intentos')->default(0);
$table->timestamp('ultimo_intento')->nullable();
$table->boolean('resuelto')->default(false);
$table->timestamps();
```

---

## Relaciones Eloquent

```php
// Trip
public function route(): BelongsTo
public function vehicle(): BelongsTo
public function conductor(): BelongsTo    // user_id
public function tickets(): HasMany
public function packages(): HasMany
public function gpsTracks(): HasMany

// Ticket
public function trip(): BelongsTo
public function vendedor(): BelongsTo     // user_id

// Package
public function trip(): BelongsTo
```

---

## Seeders recomendados

```
database/seeders/
├── RouteSeeder.php          ← Huánuco↔Llata↔Puños + ubigeos
├── RouteTariffSeeder.php    ← Matriz de precios por tramo
├── VehicleSeeder.php        ← Flota inicial
└── UserSeeder.php           ← Admin, conductor demo, counter demo
```
