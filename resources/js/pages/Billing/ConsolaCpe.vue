<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import StatusBadge from '@/Components/StatusBadge.vue'

interface Props {
  tickets: {
    data: any[]
    links: any[]
    current_page: number
  }
  filtros: {
    fecha_desde?: string
    fecha_hasta?: string
    tipo_documento?: string
    estado?: string
  }
  stats: {
    aceptados: number
    rechazados: number
    pendientes: number
    tickets_intern: number
  }
}

const props = defineProps<Props>()

const filter = (key: string, value: string) => {
  const params = { ...props.filtros, [key]: value }
  router.get('/billing/cpe', params, { preserveState: true })
}

const reintentar = (ticketId: number) => {
  router.post(`/billing/cpe/${ticketId}/reintentar`, {}, { preserveScroll: true })
}

const getEstadoLabel = (ticket: any) => {
  if (ticket.tipo_documento === 'TICKET_INTERNO') return 'ticket_interno'
  if (ticket.cdr_status === '0') return 'aceptado'
  if (ticket.cdr_status === null) return 'pendiente'
  return 'rechazado'
}

</script>

<template>
  <AppLayout>
  <Head title="Consola CPE" />

      
    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
      <div class="col-12 col-md-3">
        <div class="card h-100 border-start border-4 border-success">
          <div class="card-body">
            <h6 class="text-muted text-uppercase small mb-1">Aceptados SUNAT</h6>
            <h3 class="mb-0 fw-bold">{{ stats.aceptados }}</h3>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-3">
        <div class="card h-100 border-start border-4 border-danger">
          <div class="card-body">
            <h6 class="text-muted text-uppercase small mb-1">Rechazados</h6>
            <h3 class="mb-0 fw-bold">{{ stats.rechazados }}</h3>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-3">
        <div class="card h-100 border-start border-4 border-warning">
          <div class="card-body">
            <h6 class="text-muted text-uppercase small mb-1">Pendientes</h6>
            <h3 class="mb-0 fw-bold">{{ stats.pendientes }}</h3>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-3">
        <div class="card h-100 border-start border-4 border-secondary">
          <div class="card-body">
            <h6 class="text-muted text-uppercase small mb-1">Tickets Internos</h6>
            <h3 class="mb-0 fw-bold">{{ stats.tickets_intern }}</h3>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters & Table -->
    <div class="card">
      
      <!-- Filters -->
      <div class="card-header bg-light d-flex align-items-center gap-3 py-3">
        <div style="min-width: 200px;">
          <label class="form-label small mb-1">Estado</label>
          <select @change="(e) => filter('estado', (e.target as HTMLSelectElement).value)" class="form-select form-select-sm">
            <option value="">Todos</option>
            <option value="aceptado" :selected="filtros.estado === 'aceptado'">Aceptados</option>
            <option value="rechazado" :selected="filtros.estado === 'rechazado'">Rechazados</option>
            <option value="pendiente" :selected="filtros.estado === 'pendiente'">Pendientes</option>
            <option value="ticket_interno" :selected="filtros.estado === 'ticket_interno'">Tickets Internos</option>
          </select>
        </div>
        <!-- Se pueden agregar más filtros aquí -->
      </div>

      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>CPE</th>
                <th>Emisión</th>
                <th>Cliente</th>
                <th class="text-end">Total</th>
                <th class="text-center">Placa</th>
                <th class="text-center">Contingencia</th>
                <th class="text-center">Estado</th>
                <th class="text-end">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="t in tickets.data" :key="t.id">
                <td>
                  <span class="fw-bold">{{ t.tipo_documento }}</span><br>
                  <span v-if="t.serie_cpe" class="small text-muted">{{ t.serie_cpe }}-{{ t.correlativo_cpe }}</span>
                  <span v-else class="small text-muted">N/A</span>
                </td>
                <td class="small text-muted">
                  {{ new Date(t.emitido_en).toLocaleString('es-PE') }}
                </td>
                <td>
                  <div class="fw-medium">{{ t.cliente_nombre }}</div>
                  <div class="small text-muted">{{ t.cliente_doc }}</div>
                </td>
                <td class="text-end fw-bold text-success">S/ {{ parseFloat(t.precio).toFixed(2) }}</td>
                <td class="text-center">
                  <span class="badge bg-dark font-monospace">
                    {{ t.trip?.vehicle?.placa || 'N/A' }}
                  </span>
                </td>
                <td class="text-center">
                  <i v-if="t.emitido_en_contingencia" class="fas fa-signal-slash text-danger" title="Emitido offline"></i>
                  <span v-else class="text-muted">—</span>
                </td>
                <td class="text-center">
                  <StatusBadge :status="getEstadoLabel(t)" />
                </td>
                <td class="text-end">
                  <button 
                    v-if="t.tipo_documento === 'TICKET_INTERNO' || (t.cdr_status && t.cdr_status !== '0')"
                    @click="reintentar(t.id)" 
                    class="btn btn-warning btn-sm"
                  >
                    <i class="fas fa-sync-alt me-1"></i> Emitir CPE
                  </button>
                </td>
              </tr>
              <tr v-if="tickets.data.length === 0">
                <td colspan="8" class="text-center text-muted py-5">
                  <i class="fas fa-file-invoice fs-2 d-block mb-2"></i>
                  No hay documentos que coincidan con la búsqueda.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="tickets.data.length > 0" class="card-footer bg-light d-flex justify-content-between align-items-center">
        <span class="text-muted small">Página {{ tickets.current_page }}</span>
      </div>

    </div>
    </AppLayout>
</template>
