# SKILL.md — Frontend Web Dashboard
# Vue 3 + Inertia.js + Tailwind CSS

> Leer también: `SKILL.md` (raíz del proyecto) antes de usar este archivo.

---

## Reglas Absolutas

1. TODOS los componentes de página van en `resources/js/Pages/`
2. SIEMPRE `<script setup lang="ts">` — nunca Options API
3. CERO vistas Blade para rutas del dashboard
4. Inertia maneja la navegación — no usar `axios` directo para navegación
5. Formularios: siempre `useForm()` de Inertia, no `ref({})` manual

---

## Estructura de Carpetas

```
resources/js/
├── Pages/
│   ├── Auth/
│   │   ├── Login.vue
│   │   └── TwoFactor.vue
│   ├── Dashboard/
│   │   └── Index.vue              ← Vista principal admin
│   ├── Trips/
│   │   ├── Index.vue              ← Lista de viajes del día
│   │   ├── Create.vue             ← Abrir manifiesto
│   │   └── Show.vue               ← Detalle viaje + asientos
│   ├── Tickets/
│   │   ├── Index.vue              ← Listado con filtros
│   │   └── Show.vue
│   ├── Packages/
│   │   ├── Index.vue              ← Encomiendas
│   │   └── Create.vue
│   ├── Billing/
│   │   ├── ConsolaCpe.vue         ← Monitor SUNAT
│   │   └── SyncPanel.vue          ← Alertas tickets pendientes
│   ├── Reports/
│   │   └── LiquidacionCaja.vue    ← Reporte por viaje/conductor
│   └── Settings/
│       ├── Users/Index.vue
│       ├── Vehicles/Index.vue
│       └── Routes/Index.vue
├── Components/
│   ├── SeatMap.vue                ← Mapa de asientos reutilizable
│   ├── StatusBadge.vue            ← Badge SUNAT: Aceptado/Rechazado
│   ├── SyncAlert.vue              ← Indicator tickets pendientes
│   └── MetodoPagoBadge.vue
└── Layouts/
    └── AppLayout.vue              ← Layout principal con sidebar
```

---

## Paleta de Colores (Tailwind)

El proyecto usa verde como color primario (transportes):

```js
// tailwind.config.js — colores del sistema
colors: {
  primary: {
    50:  '#f0fdf4',
    500: '#22c55e',
    600: '#16a34a',
    700: '#15803d',
  }
}
```

Estados de CPE SUNAT:
- Aceptado → `bg-green-100 text-green-800`
- Rechazado → `bg-red-100 text-red-800`
- Pendiente → `bg-yellow-100 text-yellow-800`
- En proceso → `bg-blue-100 text-blue-800`

---

## Plantilla Base de Componente de Página

```vue
<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

interface Props {
  // definir props tipadas aquí
}

const props = defineProps<Props>()
</script>

<template>
  <AppLayout>
    <Head title="Título de la página" />

    <div class="py-6 px-4 sm:px-6 lg:px-8">
      <div class="max-w-7xl mx-auto">
        <!-- contenido -->
      </div>
    </div>
  </AppLayout>
</template>
```

---

## ConsolaCpe.vue — Especificación

Muestra tabla de comprobantes con:
- Columnas: Serie-Correlativo | Tipo | Pasajero | Monto | Estado SUNAT | Fecha emisión | Acciones
- Filtros: rango de fechas, tipo documento, estado, vehículo
- Botón "Reintentar" para CPEs con estado `Rechazado` o `cdr_errors`
- Paginación server-side via Inertia
- Exportar a Excel (llama a endpoint `/reports/cpe/export`)

Props que recibe desde el controlador:
```ts
interface Props {
  tickets: {
    data: Ticket[]
    links: PaginationLinks
    meta: PaginationMeta
  }
  filtros: {
    fecha_desde: string
    fecha_hasta: string
    estado: string
    tipo_documento: string
  }
}
```

---

## LiquidacionCaja.vue — Especificación

Reporte de ingresos por viaje:
- Header: selector de fecha + vehículo + conductor
- Cards resumen: Total Efectivo | Total Yape/Plin | Total Transferencia | TOTAL
- Tabla detalle: Hora | Asiento | Tramo | Pasajero | Monto | Método | Tipo CPE
- Fila totales al pie
- Botón imprimir / exportar PDF

---

## SeatMap.vue — Especificación

Componente reutilizable para mapa visual de asientos:

```ts
interface Props {
  vehicle: Vehicle          // tiene layout_asientos y capacidad
  asientosOcupados: number[]
  asientoSeleccionado?: number
  soloLectura?: boolean
}

const emit = defineEmits<{
  (e: 'select', asiento: number): void
}>()
```

Render: grid CSS basado en `vehicle.layout_asientos`.
Color asiento: libre = `bg-green-100`, ocupado = `bg-gray-400`, seleccionado = `bg-green-600`.

---

## Inertia — Rutas Web (routes/web.php)

```php
// Grupo con middleware auth + role
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('trips', TripController::class);
    Route::resource('tickets', TicketController::class);
    Route::resource('packages', PackageController::class);

    Route::get('/billing/cpe', [BillingController::class, 'consolaCpe'])->name('billing.cpe');
    Route::get('/billing/sync', [BillingController::class, 'syncPanel'])->name('billing.sync');
    Route::get('/reports/caja', [ReportController::class, 'liquidacion'])->name('reports.caja');
});
```

Cada controlador retorna: `Inertia::render('NombrePagina', compact('data'))`
