# SKILL.md — Aplicación Móvil
# Conductores y Counters en Ruta · Offline-First

> Leer también: `SKILL.md` (raíz del proyecto) antes de usar este archivo.

---

## Prioridad: Esta es la app principal del sistema

La app móvil es el componente más crítico. Los conductores y counters
la usan en ruta, en zonas sin cobertura. Debe funcionar 100% offline.

---

## Stack Recomendado

| Componente | Tecnología |
|---|---|
| Framework | Flutter (Dart) — recomendado por rendimiento en Android low-end |
| Alternativa | React Native (si el equipo domina JS) |
| Storage local | SQLite (`sqflite` en Flutter / `expo-sqlite` en RN) |
| HTTP | `dio` (Flutter) / `axios` (RN) |
| Estado | Riverpod (Flutter) / Zustand (RN) |
| Impresión BT | `flutter_bluetooth_printer` / `react-native-thermal-printer` |
| GPS | `geolocator` (Flutter) / `react-native-geolocation-service` |
| QR | `qr_flutter` / `react-native-qrcode-svg` |
| Escáner QR | `mobile_scanner` / `react-native-camera` |

---

## Flujo Offline-First (CRÍTICO)

```
INICIO DE APP
     │
     ├── Verificar token local válido
     │
     ├── Cargar datos del viaje activo desde SQLite
     │
VENTA DE PASAJE
     │
     ├── 1. Registrar en SQLite (inmediato, no bloquea)
     │       uuid_local = UUID.v4()
     │       sincronizado = false
     │       tipo_documento = 'TICKET_INTERNO'
     │       emitido_en = DateTime.now()  ← IMPORTANTE: hora real
     │
     ├── 2. Imprimir ticket térmico BT (con mensaje "CPE pendiente")
     │
     └── 3. Si hay conexión → POST /api/v1/tickets (online)
               │
               ├── OK: actualizar registro local, tipo_documento = 'BOLETA'
               └── Error/Sin red: queda en cola, sincronizar después

SINCRONIZACIÓN EN BACKGROUND
     │
     ├── Detectar conectividad (connectivity_plus)
     ├── Buscar registros con sincronizado = false
     ├── POST /api/v1/sync/batch con array de tickets
     └── Al recibir respuesta OK: marcar sincronizado = true
```

---

## Estructura SQLite Local

```sql
-- Tabla local de tickets pendientes
CREATE TABLE local_tickets (
  id          INTEGER PRIMARY KEY AUTOINCREMENT,
  uuid_local  TEXT UNIQUE NOT NULL,
  trip_id     INTEGER,
  data_json   TEXT NOT NULL,  -- payload completo del ticket
  sincronizado INTEGER DEFAULT 0,
  emitido_en  TEXT NOT NULL,  -- ISO 8601
  intentos    INTEGER DEFAULT 0,
  creado_en   TEXT DEFAULT CURRENT_TIMESTAMP
);

-- Config local (token, viaje activo, etc.)
CREATE TABLE local_config (
  clave TEXT PRIMARY KEY,
  valor TEXT
);
```

---

## Pantallas Principales

### 1. Login / Selección de Viaje
- Autenticación con token Bearer (guardado en SecureStorage)
- Si hay viaje activo guardado localmente → ir directo a Venta

### 2. Venta de Pasaje (pantalla principal)
- Selector de origen/destino (paradas del viaje)
- Precio calculado automáticamente desde tabla local `tarifas`
- Campo DNI (consulta API si hay red, omite si no hay)
- Mapa de asientos interactivo
- Botón "Vender" → registra y abre diálogo de impresión

### 3. Mapa de Asientos
```
┌────────────────────────────────┐
│  🚌 Placa: ABC-123             │
│  Ruta: Huánuco → Puños         │
│                                │
│  [1][2]  [ ]  [3][4]           │
│  [5][6]  [ ]  [7][8]  ← libre  │
│  [■][■]  [ ]  [9][10] ← ocupado│
│  ...                           │
│                                │
│  Disponibles: 8 / 15           │
└────────────────────────────────┘
```

### 4. Encomiendas
- Form: remitente, destinatario, descripción, peso, precio, estado pago
- Genera QR único: `QR = SHA256(uuid_local + trip_id + timestamp)`
- Imprime etiqueta con QR

### 5. Escáner QR — Entrega
- Abre cámara
- Lee QR de la encomienda
- Muestra datos y botón "Confirmar entrega"
- Actualiza estado → `entregado`

### 6. Estado de Sincronización (indicador siempre visible)
```
🔴 5 tickets pendientes de sincronizar
🟡 Sincronizando... (3/5)
🟢 Todo sincronizado
```

---

## Formato del Ticket Térmico (80mm)

```
================================
  SHINHUA - TRANSPORTES PUÑOS
================================
Ticket: TKT-2026-00423
Contingencia: CPE pendiente

PASAJERO: JUAN PEREZ QUISPE
DNI: 45678901
ASIENTO: 7
TRAMO: Huánuco → Llata
FECHA: 26/06/2026 14:32
PRECIO: S/ 15.00
PAGO: Efectivo

[QR code de seguimiento]

Su boleta electrónica será
enviada al recuperar señal.
================================
Conductor: Pedro Ramos
Placa: ABC-123  Manif: M-0042
================================
```

---

## Endpoint de Sync Batch — Payload

```json
POST /api/v1/sync/batch
Authorization: Bearer {token}

{
  "trip_id": 15,
  "tickets": [
    {
      "uuid_local": "550e8400-e29b-41d4-a716-446655440000",
      "numero_asiento": 7,
      "origen_tramo": "Huánuco",
      "destino_tramo": "Llata",
      "ubigeo_origen": "100101",
      "ubigeo_destino": "100301",
      "dni_pasajero": "45678901",
      "nombre_pasajero": "Juan Pérez Quispe",
      "precio": 15.00,
      "metodo_pago": "efectivo",
      "emitido_en": "2026-06-26T14:32:15-05:00",
      "emitido_en_contingencia": true
    }
  ]
}
```

Respuesta esperada:
```json
{
  "procesados": 1,
  "duplicados": 0,
  "errores": 0,
  "detalle": [
    {
      "uuid_local": "550e8400...",
      "estado": "emitido",
      "tipo_documento": "BOLETA",
      "serie": "B001",
      "correlativo": 423
    }
  ]
}
```

---

## GPS Background

- Enviar coordenadas cada 30 segundos durante el viaje
- Solo si `trip.estado = 'en_ruta'`
- Usar batería mínima: precisión media, sin wakeLock agresivo

```
POST /api/v1/trips/{trip_id}/gps
{ "lat": -9.9306, "lng": -76.2422, "velocidad_kmh": 45.2 }
```

---

## Consideraciones de Hardware

Los conductores usan celulares Android de gama baja (2-3GB RAM).
- Evitar animaciones pesadas
- Imágenes comprimidas, no SVG complejos
- SQLite es suficiente, no usar Hive ni Isar
- APK máximo recomendado: 25MB
- Impresora térmica: protocolo ESC/POS vía Bluetooth Classic
