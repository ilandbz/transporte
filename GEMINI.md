# GEMINI.md
# Sistema de Transportes Shinhua
# Contexto e instrucciones para Gemini CLI

> Este documento define las reglas obligatorias para cualquier modificación del proyecto.
> Antes de generar código, analizar la arquitectura existente y respetarla.
> Nunca reestructurar el proyecto sin solicitarlo explícitamente.

---

# Descripción del proyecto

Sistema de gestión para la empresa Transportes Shinhua.

Incluye:

- Gestión de usuarios
- Gestión de vehículos
- Gestión de rutas
- Gestión de tarifas
- Gestión de sucursales (Branches)
- Módulo de Encomiendas (Packages)
- Emisión electrónica SUNAT mediante Greenter
- Panel de Sincronización (Offline/Contingencias)
- Dashboard administrativo
- API REST para aplicación móvil
- Autenticación mediante Sanctum
- Procesamiento en cola (Jobs) para emisión SUNAT

---

# Estado del proyecto

## Backend

Estado: COMPLETADO

Implementado:

- Laravel 13
- PHP 8.4
- Sanctum
- API Versionada (/api/v1)
- 12 endpoints protegidos
- Services
- FormRequest
- Eloquent Models
- Jobs (SyncBatchJob para emisión asíncrona)
- Policies
- Middleware (Manejo de Sucursal Activa vía sesión y HandleInertiaRequests)
- Multi-sucursal: Controladores de sucursal, encomiendas, pasajes
- SQLite para desarrollo

Integración SUNAT:

- Greenter
- Certificado PEM
- SUNAT Beta operativo
- Sistema de contingencia (sincronización forzada en caso de desconexión)

---

## Frontend

Estado: EN DESARROLLO

Tecnologías:

- Vue 3
- Inertia.js
- TypeScript
- Bootstrap 5
- Falcon v3.16
- FontAwesome 6
- SweetAlert2

CRUD implementados:

✔ Usuarios

✔ Vehículos

✔ Tarifas

✔ Rutas

✔ Sucursales (CRUD y selector global para admin)

✔ Encomiendas (Registro y reportes)

✔ Tickets/Pasajes (Emisión, anulación, confirmación, impresión)

✔ Panel de Sincronización (SUNAT Contingencias)

Dashboard implementado.

Login implementado/pendiente integración fina.

---

# Stack tecnológico (OBLIGATORIO)

Backend

- Laravel 13
- PHP 8.4

Frontend

- Vue 3
- Composition API
- script setup
- TypeScript

UI

- Falcon Bootstrap 5

Base de datos

- SQLite (desarrollo)

Autenticación

- Laravel Sanctum

Colas

- Laravel Queue

Facturación

- Greenter

---

# Package Manager

Utilizar únicamente

- pnpm

o

- yarn

Nunca utilizar

- npm

---

# Arquitectura

Seguir estrictamente la arquitectura existente.

No crear nuevas capas innecesarias.

Mantener:

Controllers
↓

Services
↓

Models

La lógica de negocio pertenece a Services.

Los Controllers únicamente deben:

- validar
- llamar Services
- retornar respuestas

---

# Convenciones Laravel

Utilizar siempre

FormRequest

para validaciones.

Utilizar:

Policies

cuando exista autorización.

Usar:

Eloquent

No escribir consultas SQL innecesarias.

Preferir:

Relationships

Scopes

Accessors

Mutators

cuando corresponda.

---

# Convenciones Vue

Siempre utilizar

```vue
<script setup lang="ts">
```

Composition API únicamente.

No utilizar Options API.

No utilizar mixins.

Preferir:

ref()

computed()

watch()

defineProps()

defineEmits()

Mantener componentes pequeños.

Evitar componentes gigantes.

---

# Convenciones Bootstrap

Este proyecto utiliza exclusivamente:

Falcon Bootstrap 5

Por lo tanto utilizar:

```
btn btn-primary
btn btn-success
btn btn-danger

card

card-header

card-body

table

table-hover

table-striped

badge

alert

dropdown

modal
```

No utilizar:

Tailwind

No utilizar:

Bulma

No utilizar:

Material UI

No utilizar:

PrimeVue

No utilizar:

Vuetify

---

# Layout

Utilizar únicamente las clases Falcon.

Ejemplo:

```
navbar-vertical

navbar-top

content

card

row

col

container-fluid
```

No modificar el layout general.

---

# CSS

No utilizar:

style="..."

salvo casos estrictamente necesarios.

No utilizar CSS para reemplazar clases Bootstrap.

Preferir clases utilitarias Bootstrap.

---

# Iconografía

Utilizar únicamente:

FontAwesome 6

Ejemplo

```html
<i class="fas fa-user"></i>

<i class="fas fa-bus"></i>

<i class="fas fa-route"></i>
```

No utilizar emojis como iconos.

---

# Modales

Siempre utilizar

Bootstrap Modal

Ejemplo

```javascript
new bootstrap.Modal(...)
```

Nunca utilizar

window.confirm()

Las confirmaciones deben realizarse con

SweetAlert2

Ejemplo

```javascript
Swal.fire(...)
```

---

# Tablas

Preferir

```
table

table-hover

table-striped

align-middle
```

No construir tablas mediante CSS personalizado.

---

# Formularios

Utilizar Bootstrap Forms.

Ejemplo

```
form-control

form-select

input-group

form-check
```

Mantener consistencia visual.

---

# Assets Falcon

Ubicación

```
public/vendor/falcon/
```

Orden de carga

HEAD

```
simplebar

config.js

simplebar.css

theme.min.css

vite

inertiaHead
```

BODY

```
popper

bootstrap

anchorjs

is.min.js

fontawesome

lodash

theme.js
```

No alterar este orden.

---

# Error conocido

Existe el siguiente error:

```
window.AnchorJS is not a constructor
```

Solución:

Copiar

```
vendors/anchorjs/anchor.min.js
```

hacia

```
public/vendor/falcon/vendors/anchorjs/
```

No modificar theme.js.

---

# Convenciones TypeScript

Utilizar tipado explícito cuando sea posible.

Evitar:

any

Preferir:

interfaces

types

No desactivar validaciones.

---

# Convenciones API

Las rutas deben mantenerse versionadas.

Ejemplo

```
/api/v1/
```

Mantener autenticación mediante

Sanctum Bearer Token.

---

# Convenciones Base de Datos

Utilizar:

Migraciones

Factories

Seeders

No modificar migraciones existentes.

Crear nuevas migraciones cuando sea necesario.

---

# Convenciones Git

Generar cambios pequeños.

No modificar archivos no relacionados.

Evitar refactors masivos.

---

# Antes de escribir código

Gemini debe:

1. Analizar el código existente.

2. Reutilizar componentes existentes.

3. Mantener el estilo del proyecto.

4. No introducir nuevas dependencias si no son necesarias.

5. No cambiar la arquitectura.

6. No cambiar Bootstrap por otro framework.

7. No reemplazar Falcon.

8. Mantener TypeScript.

9. Mantener Inertia.

10. Mantener Composition API.

---

# Nunca hacer

Nunca utilizar

- Tailwind
- Vuetify
- PrimeVue
- Material UI
- Bulma
- jQuery
- Options API
- CSS inline para layout
- window.confirm()
- alert()
- prompt()

---

# Siempre hacer

Siempre utilizar

- Bootstrap 5
- Falcon
- FontAwesome
- SweetAlert2
- Composition API
- TypeScript
- Laravel Services
- FormRequest
- Sanctum
- Eloquent
- Relaciones Eloquent
- Bootstrap Modal

---

# Objetivo principal

Todo el código generado debe parecer escrito por el mismo desarrollador que creó el proyecto.

La prioridad es mantener la consistencia del código existente, reutilizar componentes y respetar completamente la arquitectura del sistema antes que introducir nuevas tecnologías o patrones.

---

# Lógica de Negocio — Rutas y Tarifas

- **Ruta Principal:** Trayecto completo de origen a destino final (ej. Huánuco - Puños).
- **Tramos (Paradas intermedias):** Una ruta principal suele tener paradas (ej. Llata). Por eso, dentro de "Huánuco - Puños", se configuran tarifas para sub-trayectos (Huánuco → Llata, Llata → Puños) para pasajeros que suben o bajan a medio camino.
- **Clase:** Nivel de servicio ofrecido (ej. "normal", "vip"). Permite tener precios distintos para el mismo tramo físico dependiendo de la comodidad o tipo de vehículo.

# Lógica de Negocio — Sucursales (Branches)

- **Sucursal Asignada:** Los usuarios (`counter`, `conductor`) operan dentro de su sucursal fija asignada.
- **Admin Global:** El `admin` tiene un selector global en la barra superior (Top Navbar). La elección se guarda en la sesión (`active_branch_id`) y altera en tiempo real los registros que visualiza y emite (Reportes, Ventas, Encomiendas).
- Todos los pasajes (Tickets) y Encomiendas (Packages) almacenan obligatoriamente el `branch_id` de la sucursal activa al momento de la venta.

# Lógica de Negocio — Sincronización SUNAT (Jobs y Contingencias)

- Al emitir un pasaje/encomienda, si hay conexión, se envía a SUNAT. Si falla, o si se marca como "contingencia", se guarda como `sincronizado = false`.
- Existe un `SyncBatchJob` que procesa los documentos pendientes en segundo plano (`queue:work`).
- Si el Job falla, o por decisión administrativa, en el **Panel de Facturación** (SyncPanel) existe la opción "Forzar Sincronización Global" para reintentar masivamente la emisión de todos los documentos pendientes.