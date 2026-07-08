# CLAUDE.md — Contexto del Proyecto
# Sistema de Transportes Shinhua · Para agentes IA

> Leer también: `SKILL.md` antes de generar cualquier código.

---

## Estado del proyecto al 08/07/2026

### ✅ BACKEND COMPLETADO Y VERIFICADO
- 12+ rutas API `/api/v1/` con `auth:sanctum` ✅
- Auth móvil: `POST /api/v1/auth/login` → token Bearer ✅
- 13+ modelos Eloquent completos (incluyendo Branch, Package, Client, etc.) ✅
- 5 Services (SunatGreenterService completo con Greenter y modo contingencia) ✅
- SUNAT Beta: Boletas/Facturas aceptadas (CDR code 0) ✅
- Certificado: `certs/cert.pem` ✅
- Jobs de procesamiento en cola (`SyncBatchJob`) para facturación asíncrona ✅
- Soporte Multi-Sucursal (Branches) manejado por Middleware y Sesión global para admins ✅

### ✅ DASHBOARD WEB — FALCON BOOTSTRAP 5 ACTIVO
- Falcon v3.16.0 en `/public/vendor/falcon/` ✅
- `app.blade.php` carga assets Falcon correctamente ✅
- AppLayout.vue con navbar-vertical nativo de Falcon ✅
- Dashboard con cards KPI y iconos FontAwesome ✅
- Configuración → Usuarios CRUD (con asignación obligatoria de sucursal) ✅
- Configuración → Vehículos CRUD + toggle activo ✅
- Configuración → Rutas + Tarifas (tabla expandible) ✅
- Configuración → Sucursales CRUD ✅
- Operaciones → Encomiendas (CRUD y facturación de envíos) ✅
- Operaciones → Panel de Sincronización (para reintentar boletas offline o con errores) ✅
- Reportes → Liquidación de Caja (incluyendo boletos y encomiendas, con filtro de sucursal) ✅
- Top Navbar → Selector global de sucursal para Administradores ✅
- Login Falcon card-style ✅ (pendiente integración final si corresponde)

### ⚠️ ERROR MENOR CONOCIDO
- `window.AnchorJS is not a constructor` en theme.js — no afecta funcionalidad
- Fix: copiar `vendors/anchorjs/anchor.min.js` a `/public/vendor/falcon/vendors/anchorjs/`

---

## Stack Tecnológico DEFINITIVO

| Capa | Tecnología |
|---|---|
| Backend | Laravel 13 + PHP 8.4 |
| Auth API | Sanctum + token Bearer |
| Frontend CSS | **Falcon Bootstrap 5** (NO Tailwind para UI) |
| Frontend JS | Vue 3 + Inertia.js + `<script setup lang="ts">` |
| Iconos | FontAwesome 6 (vía Falcon vendors) |
| Modales | Bootstrap Modal nativo + SweetAlert2 |
| Sidebar | Falcon `navbar-vertical` nativo |
| SUNAT | Greenter (Beta verificado, cert.pem) |
| DB local | SQLite (Herd) |
| Package manager | **pnpm** o **yarn** — NUNCA npm |

---

## Estructura Falcon en `/public/vendor/falcon/`

```
css/
  theme.min.css          ← CSS principal (Bootstrap 5 + Falcon + FA incluidos)
js/
  config.js              ← cargar PRIMERO en <head>
  theme.js               ← cargar al final de <body>
  flatpickr.js
vendors/
  simplebar/             ← scroll sidebar
  bootstrap/             ← bootstrap.min.js
  popper/
  fontawesome/           ← all.min.js (FA6)
  anchorjs/              ← anchor.min.js (error conocido si falta)
  echarts/               ← gráficos
  flatpickr/
img/                     ← imágenes Falcon
```

## Orden correcto en app.blade.php

```html
<head>
  <script src="/vendor/falcon/vendors/simplebar/simplebar.min.js"></script>
  <script src="/vendor/falcon/js/config.js"></script>
  <link href="/vendor/falcon/vendors/simplebar/simplebar.min.css" rel="stylesheet">
  <link href="/vendor/falcon/css/theme.min.css" rel="stylesheet" id="style-default">
  @vite(...)
  @inertiaHead
</head>
<body>
  @inertia
  <script src="/vendor/falcon/vendors/popper/popper.min.js"></script>
  <script src="/vendor/falcon/vendors/bootstrap/bootstrap.min.js"></script>
  <script src="/vendor/falcon/vendors/anchorjs/anchor.min.js"></script>
  <script src="/vendor/falcon/vendors/is/is.min.js"></script>
  <script src="/vendor/falcon/vendors/fontawesome/all.min.js"></script>
  <script src="/vendor/falcon/vendors/lodash/lodash.min.js"></script>
  <script src="/vendor/falcon/js/theme.js"></script>
</body>
```

---

## Reglas para código Vue con Falcon

```
✅ USAR:  btn btn-primary, btn-success, card, table table-hover
✅ USAR:  badge bg-success, bg-danger, bg-warning
✅ USAR:  <i class="fas fa-bus"></i> para iconos
✅ USAR:  Swal.fire({...}) para confirmaciones
✅ USAR:  new bootstrap.Modal(ref) para modales
✅ USAR:  navbar-vertical, content, navbar-top (clases Falcon)

❌ NO:   clases Tailwind (bg-green-700, px-4, rounded-xl)
❌ NO:   CSS inline para layout (margin-left, position: fixed)
❌ NO:   emojis como iconos
❌ NO:   confirm() nativo del browser
```

---

## Credenciales

```
Admin:     admin@shinhua.pe       / Admin2026*
Conductor: pedro.conductor@shinhua.pe / Conductor2026*
RUC SUNAT: 20603371292
SOL Beta:  20603371292MODDATOS / moddatos
```

---

## Errores resueltos

| Error | Solución |
|---|---|
| `routes/api.php` no existía | Creado + registrado en bootstrap/app.php |
| Sanctum no instalado | `composer require laravel/sanctum` |
| Greenter necesita .pem | `openssl pkcs12 -in cert.pfx -out cert.pem -nodes` |
| Sidebar duplicado | Reemplazar AppLayout.vue del starter kit |
| Modelos vacíos | Reescritos con fillable/casts/relaciones |
| Falcon sin iconos FA | Copiar vendors/fontawesome a public/vendor/falcon/vendors/ |
| AnchorJS error | Copiar vendors/anchorjs a public/vendor/falcon/vendors/ |

---

## Lógica de Negocio — Rutas y Tarifas

- **Ruta Principal:** Trayecto completo de origen a destino final (ej. Huánuco - Puños).
- **Tramos (Paradas intermedias):** Una ruta principal suele tener paradas (ej. Llata). Por eso, dentro de "Huánuco - Puños", se configuran tarifas para sub-trayectos (Huánuco → Llata, Llata → Puños) para pasajeros que suben o bajan a medio camino.
- **Clase:** Nivel de servicio ofrecido (ej. "normal", "vip"). Permite tener precios distintos para el mismo tramo físico dependiendo de la comodidad o tipo de vehículo.

## Lógica de Negocio — Sucursales (Branches)

- **Sucursal Asignada:** Los usuarios (`counter`, `conductor`) operan dentro de su sucursal fija asignada.
- **Admin Global:** El `admin` tiene un selector global en la barra superior (Top Navbar). La elección se guarda en la sesión (`active_branch_id`) y altera en tiempo real los registros que visualiza y emite (Reportes, Ventas, Encomiendas).
- Todos los pasajes (Tickets) y Encomiendas (Packages) almacenan obligatoriamente el `branch_id` de la sucursal activa al momento de la venta.

## Lógica de Negocio — Sincronización SUNAT (Jobs y Contingencias)

- Al emitir un pasaje/encomienda, si hay conexión, se envía a SUNAT. Si falla, o si se marca como "contingencia", se guarda como `sincronizado = false`.
- Existe un `SyncBatchJob` que procesa los documentos pendientes en segundo plano (`queue:work`).
- Si el Job falla, o por decisión administrativa, en el **Panel de Facturación** (SyncPanel) existe la opción "Forzar Sincronización Global" para reintentar masivamente la emisión de todos los documentos pendientes.
