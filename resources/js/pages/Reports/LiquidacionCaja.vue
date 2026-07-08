<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref } from 'vue'

interface Ticket {
  id: number
  emitido_en: string
  numero_asiento: number
  origen_tramo: string
  destino_tramo: string
  nombre_pasajero: string | null
  precio: string
  metodo_pago: string
  numero_completo: string
}

interface Package {
  id: number
  emitido_en: string
  remitente_nombre: string
  destinatario_nombre: string
  precio: string
  estado_pago: string
  numero_completo?: string
}

interface Props {
  resumen: {
    total_efectivo: number
    total_yape: number
    total_plin: number
    total_transferencia: number
    total_general: number
    cantidad_tickets: number
    cantidad_encomiendas: number
  }
  tickets: Ticket[]
  packages: Package[]
  filtros: { fecha: string; vehicle_id: number | null; user_id: number | null }
  vehiculos: { id: number; placa: string }[]
  conductores: { id: number; name: string }[]
}

const props = defineProps<Props>()
const form = ref({ ...props.filtros })

function consultar() {
  router.get('/reports/caja', form.value, { preserveState: true })
}

function formatMoney(v: number) {
  return `S/ ${Number(v).toFixed(2)}`
}

function formatTime(d: string) {
  return new Date(d).toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' })
}

const metodoBadge: Record<string, string> = {
  efectivo:      'bg-success',
  yape:          'bg-primary', // Fallback for purple
  plin:          'bg-info',
  transferencia: 'bg-secondary',
}
</script>

<template>
  <AppLayout>
      <Head title="Liquidación de Caja" />

    <!-- Filtros -->
    <div class="card mb-4">
      <div class="card-body d-flex flex-wrap align-items-end gap-3">
        <div>
          <label class="form-label small mb-1">Fecha</label>
          <input type="date" v-model="form.fecha" class="form-control form-control-sm" />
        </div>
        <div>
          <label class="form-label small mb-1">Vehículo</label>
          <select v-model="form.vehicle_id" class="form-select form-select-sm">
            <option :value="null">Todos</option>
            <option v-for="v in vehiculos" :key="v.id" :value="v.id">{{ v.placa }}</option>
          </select>
        </div>
        <div>
          <label class="form-label small mb-1">Conductor</label>
          <select v-model="form.user_id" class="form-select form-select-sm">
            <option :value="null">Todos</option>
            <option v-for="c in conductores" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
        </div>
        <button @click="consultar" class="btn btn-success btn-sm px-3">
          Consultar
        </button>
        <div class="ms-auto">
          <button @click="() => window.print()" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-print me-1"></i> Imprimir
          </button>
        </div>
      </div>
    </div>

    <!-- Cards resumen -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md">
        <div class="card h-100 bg-light border-success">
          <div class="card-body text-center">
            <h6 class="text-success small text-uppercase fw-bold mb-2">Efectivo</h6>
            <h4 class="mb-0 text-success fw-bold">{{ formatMoney(resumen.total_efectivo) }}</h4>
          </div>
        </div>
      </div>
      <div class="col-6 col-md">
        <div class="card h-100 bg-light border-primary">
          <div class="card-body text-center">
            <h6 class="text-primary small text-uppercase fw-bold mb-2">Yape</h6>
            <h4 class="mb-0 text-primary fw-bold">{{ formatMoney(resumen.total_yape) }}</h4>
          </div>
        </div>
      </div>
      <div class="col-6 col-md">
        <div class="card h-100 bg-light border-info">
          <div class="card-body text-center">
            <h6 class="text-info small text-uppercase fw-bold mb-2">Plin</h6>
            <h4 class="mb-0 text-info fw-bold">{{ formatMoney(resumen.total_plin) }}</h4>
          </div>
        </div>
      </div>
      <div class="col-6 col-md">
        <div class="card h-100 bg-light border-secondary">
          <div class="card-body text-center">
            <h6 class="text-secondary small text-uppercase fw-bold mb-2">Transferencia</h6>
            <h4 class="mb-0 text-secondary fw-bold">{{ formatMoney(resumen.total_transferencia) }}</h4>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-auto" style="min-width: 200px;">
        <div class="card h-100 bg-success text-white shadow">
          <div class="card-body text-center">
            <h6 class="text-white-50 small text-uppercase fw-bold mb-2">Total General</h6>
            <h3 class="mb-0 fw-bold">{{ formatMoney(resumen.total_general) }}</h3>
            <p class="mb-0 small text-white-50 mt-1">{{ resumen.cantidad_tickets }} tickets</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabla detalle -->
    <div class="card">
      <div class="card-header bg-light">
        <h6 class="mb-0 fw-semibold">Detalle de pasajes</h6>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped align-middle mb-0">
            <thead class="table-light text-muted small">
              <tr>
                <th>Hora</th>
                <th class="text-center">Asiento</th>
                <th>Tramo</th>
                <th>Pasajero</th>
                <th class="text-end">Monto</th>
                <th class="text-center">Método</th>
                <th>CPE</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="t in tickets" :key="t.id">
                <td class="small text-muted">{{ formatTime(t.emitido_en) }}</td>
                <td class="text-center fw-medium">{{ t.numero_asiento }}</td>
                <td class="small">{{ t.origen_tramo }} <i class="fas fa-arrow-right text-muted mx-1"></i> {{ t.destino_tramo }}</td>
                <td>{{ t.nombre_pasajero ?? '—' }}</td>
                <td class="text-end fw-bold">S/ {{ Number(t.precio).toFixed(2) }}</td>
                <td class="text-center">
                  <span :class="['badge', metodoBadge[t.metodo_pago] ?? 'bg-secondary']">
                    {{ t.metodo_pago }}
                  </span>
                </td>
                <td class="font-monospace small text-muted">{{ t.numero_completo }}</td>
              </tr>
              <tr v-if="tickets.length === 0">
                <td colspan="7" class="text-center text-muted py-5">
                  <i class="fas fa-file-alt fs-2 d-block mb-2"></i>
                  No hay registros para esta consulta.
                </td>
              </tr>
              <!-- Fila total -->
              <tr v-if="tickets.length > 0" class="table-success fw-bold">
                <td colspan="4" class="text-end text-success">TOTAL</td>
                <td class="text-end text-success fs-5">{{ formatMoney(resumen.total_general) }}</td>
                <td colspan="2"></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Tabla detalle encomiendas -->
    <div class="card mt-4 mb-4">
      <div class="card-header bg-light">
        <h6 class="mb-0 fw-semibold">Detalle de encomiendas</h6>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped align-middle mb-0">
            <thead class="table-light text-muted small">
              <tr>
                <th>Hora</th>
                <th>Remitente</th>
                <th>Destinatario</th>
                <th class="text-end">Monto</th>
                <th class="text-center">Estado de Pago</th>
                <th>CPE</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in packages" :key="p.id">
                <td class="small text-muted">{{ formatTime(p.emitido_en) }}</td>
                <td class="small">{{ p.remitente_nombre }}</td>
                <td class="small">{{ p.destinatario_nombre }}</td>
                <td class="text-end fw-bold">S/ {{ Number(p.precio).toFixed(2) }}</td>
                <td class="text-center">
                  <span :class="['badge', p.estado_pago === 'pagado' ? 'bg-success' : 'bg-warning']">
                    {{ p.estado_pago }}
                  </span>
                </td>
                <td class="font-monospace small text-muted">{{ p.numero_completo ?? '—' }}</td>
              </tr>
              <tr v-if="packages.length === 0">
                <td colspan="6" class="text-center text-muted py-5">
                  <i class="fas fa-box fs-2 d-block mb-2"></i>
                  No hay registros de encomiendas.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    </AppLayout>
</template>
