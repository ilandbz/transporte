# SKILL.md — Sistema de Transportes Shinhua
# Asociación de Transportistas Shinhua de Puños

> **Leer este archivo COMPLETO antes de generar cualquier código.**
> Este es el documento maestro de contexto para agentes IA en este proyecto.

---

## 1. Stack Tecnológico (NO negociable)

| Capa | Tecnología |
|---|---|
| Backend | Laravel 13 (PHP 8.4+) |
| Auth | Laravel Sanctum ✅ INSTALADO (`laravel/sanctum`) |
| Frontend Web | Vue 3 + Inertia.js + Tailwind CSS |
| Sintaxis Vue | `<script setup lang="ts">` (Composition API) SIEMPRE |
| Ubicación vistas | `resources/js/Pages/` ÚNICAMENTE |
| Blade | PROHIBIDO para vistas nuevas |
| Facturación | Greenter (PHP) — pendiente instalar |
| App Móvil | Flutter o React Native (API REST) |
| Base de Datos | SQLite (Herd local) / PostgreSQL (producción) |
| ORM | Eloquent + Migraciones Laravel |
| **Package manager JS** | **pnpm** (preferido) o **yarn** — NUNCA npm |

---

## 2. Comandos de Package Manager (CRÍTICO)

```bash
# CORRECTO — usar siempre pnpm o yarn
pnpm install
pnpm dev
pnpm build
pnpm add [paquete]

# Si pnpm no está disponible, usar yarn
yarn install
yarn dev
yarn build
yarn add [paquete]

# PROHIBIDO — nunca sugerir npm
npm install   ← NUNCA
npm run dev   ← NUNCA
```

---

## 3. Arquitectura de Software

### Thin Controllers (OBLIGATORIO)
Los controladores NO deben contener lógica de negocio.
Toda lógica va en `app/Services/`.

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Api/                    ← 6 controllers API ✅ CREADOS
│   │   │   ├── TripController.php
│   │   │   ├── TicketController.php
│   │   │   ├── PackageController.php
│   │   │   ├── SyncController.php
│   │   │   ├── GpsController.php
│   │   │   └── ConsultaController.php
│   │   ├── DashboardController.php ✅
│   │   ├── BillingController.php   ✅
│   │   ├── ReportController.php    ✅
│   │   └── TripWebController.php   ✅
│   └── Resources/
│       ├── TicketResource.php      ✅
│       └── PackageResource.php     ✅
├── Models/                         ✅ TODOS CREADOS
├── Services/                       ✅ TODOS CREADOS
│   ├── TicketService.php
│   ├── SyncService.php
│   ├── SunatGreenterService.php    ← stub, implementar en PROMPT-06
│   ├── DniRucApiService.php
│   └── GpsTrackingService.php
└── Jobs/
    └── SyncBatchJob.php            ✅
```

### Rutas API ✅ VERIFICADAS
- Archivo: `routes/api.php` — YA EXISTE
- Prefijo `/api/v1/`
- Middleware: `auth:sanctum`
- **12 rutas funcionando** — verificado con curl

### Rutas Web ✅
- Archivo: `routes/web.php` — rutas dashboard agregadas
- `bootstrap/app.php` — api.php registrado

---

## 4. Estado de Migraciones ✅ TODAS EJECUTADAS

> NO volver a crear ninguna de estas. Ya existen y tienen datos seed.

| Tabla | Registros seed |
|---|---|
| users | 4 (admin, 2 conductores, 1 counter) |
| routes | 3 (Huánuco-Puños, Huánuco-Llata, Llata-Puños) |
| route_tariffs | 8 (matriz completa ida/vuelta) |
| vehicles | 3 (ABC-123 minivan, DEF-456 coaster, GHI-789 minivan) |
| trips | — |
| tickets | — |
| packages | — |
| gps_tracks | — |
| cpe_errors | — |
| dni_cache | — |
| personal_access_tokens | creada por Sanctum ✅ |

---

## 5. Modelos Eloquent ✅ TODOS COMPLETOS

Todos los modelos tienen `$fillable`, `$casts`, relaciones y scopes.
Críticos a recordar:

- `User` — tiene `HasApiTokens` (Sanctum), campo `role` (admin/conductor/counter)
- `Trip` — tiene `isAsientoOcupado()`, `ocuparAsiento()`, `liberarAsiento()`
- `Ticket` — tiene accessors `numero_completo` y `esta_emitido`
- `GpsTrack` — tiene `public $timestamps = false`
- `Route` — relación `tariffs()` → RouteTariff

---

## 6. Campos de Auditoría Asíncrona (OBLIGATORIO en tickets y packages)

```php
$table->uuid('uuid_local')->unique();
$table->boolean('sincronizado')->default(false);
$table->boolean('emitido_en_contingencia')->default(false);
$table->enum('tipo_documento', ['BOLETA', 'FACTURA', 'TICKET_INTERNO'])->default('TICKET_INTERNO');
$table->timestamp('emitido_en')->nullable();    // fecha REAL de venta — nunca now()
$table->timestamp('sincronizado_en')->nullable();
```

---

## 7. Campos SUNAT Obligatorios — Transportes (Catálogo N°19)

```php
$table->string('placa_vehiculo', 10);
$table->string('ubigeo_origen', 6);    // 6 dígitos — Huánuco=100101, Llata=100301, Puños=100801
$table->string('ubigeo_destino', 6);
$table->string('dni_pasajero', 15)->nullable();
$table->string('nombre_pasajero')->nullable();
$table->string('numero_manifiesto')->nullable();
```

---

## 8. Flujo de Conectividad Híbrida

```
APP MÓVIL
    ├── CON SEÑAL ──► POST /api/v1/tickets ──► TicketService ──► [SunatGreenterService] ──► SUNAT
    └── SIN SEÑAL ──► SQLite local (TICKET_INTERNO, sincronizado=false)
                          └── AL RECUPERAR SEÑAL ──► POST /api/v1/sync/batch
                                                         └── SyncService ──► SyncBatchJob (queue)
                                                             (usa emitido_en ORIGINAL, nunca now())
```

---

## 9. API verificada con curl ✅

```bash
# Generar token para pruebas
php artisan tinker
>>> $u = App\Models\User::where('email','admin@shinhua.pe')->first()
>>> echo $u->createToken('test')->plainTextToken

# Test trips — respuesta 201 ✅
POST /api/v1/trips  { route_id:1, vehicle_id:1, fecha_salida, numero_manifiesto }

# Test seats — respuesta 200 ✅
GET /api/v1/trips/1/seats → { total:10, ocupados:[], disponibles:[1..10] }

# Test tickets — respuesta 201 ✅
POST /api/v1/tickets { trip_id, uuid_local, numero_asiento, origen_tramo,
                       destino_tramo, ubigeo_*, metodo_pago, tipo_documento,
                       emitido_en, emitido_en_contingencia }
# precio calculado automáticamente desde route_tariffs (Huánuco→Llata = S/15.00)
```

---

## 10. Rutas del Negocio y Ubigeos

| Ruta | Origen | Destino | Ubigeo O. | Ubigeo D. |
|---|---|---|---|---|
| Principal | Huánuco | Puños | 100101 | 100801 |
| Parcial A | Huánuco | Llata | 100101 | 100301 |
| Parcial B | Llata | Puños | 100301 | 100801 |

Tarifas normales: Huánuco↔Puños S/25, Huánuco↔Llata S/15, Llata↔Puños S/12

---

## 11. Roles de Usuario

| Rol | Email seed | Acceso |
|---|---|---|
| `admin` | admin@shinhua.pe | Dashboard Web completo |
| `conductor` | pedro.conductor@shinhua.pe / carlos.conductor@shinhua.pe | App Móvil |
| `counter` | maria.counter@shinhua.pe | App Móvil + Dashboard básico |

---

## 12. Reglas para Generar Código Frontend Vue

- Archivo: `resources/js/Pages/[Modulo]/[Componente].vue`
- SIEMPRE `<script setup lang="ts">`
- Props tipadas con interfaces TypeScript
- Imports Inertia: `import { useForm, router } from '@inertiajs/vue3'`
- Solo clases Tailwind CSS
- NUNCA Options API (`data()`, `methods:`, `computed:`)
- NUNCA `axios` directo — toda navegación por Inertia `router` o `useForm`
- Precios: `S/ ${Number(precio).toFixed(2)}`
- Fechas: `new Date(fecha).toLocaleDateString('es-PE')`
- **Compilar con:** `pnpm build` o `yarn build`

---

## 13. Convenciones de Nomenclatura

| Elemento | Convención | Ejemplo |
|---|---|---|
| Modelos | PascalCase singular | `Ticket`, `Vehicle` |
| Tablas | snake_case plural | `tickets`, `vehicles` |
| Controladores API | PascalCase + Controller en `Api/` | `Api\TicketController` |
| Controladores Web | PascalCase + Controller en raíz | `BillingController` |
| Services | PascalCase + Service | `TicketService` |
| Jobs | PascalCase + Job | `SyncBatchJob` |
| Rutas API | kebab-case con prefijo v1 | `/api/v1/sync/batch` |
| Componentes Vue | PascalCase | `SeatMapWidget.vue` |

---

## 14. Lecciones aprendidas — Errores a NO repetir

1. `routes/api.php` NO existe por defecto en Laravel 13 — debe crearse Y registrarse en `bootstrap/app.php`
2. `laravel/sanctum` NO viene instalado por defecto — instalar con `composer require laravel/sanctum`
3. Los modelos generados con `make:model` quedan vacíos — siempre completar `$fillable`, `$casts` y relaciones
4. `Route` es palabra reservada de Laravel — en seeders usar `use App\Models\Route as RouteModel`
5. `GpsTrack` debe tener `public $timestamps = false`
6. NUNCA usar `npm` — solo `pnpm` o `yarn`
7. SunatGreenterService es stub hasta PROMPT-06 — no intentar implementarlo antes
