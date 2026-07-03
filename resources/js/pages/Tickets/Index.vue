<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import StatusBadge from '@/Components/StatusBadge.vue'

declare const Swal: any

interface Props {
  tickets: {
    data: any[]
    links: any[]
    current_page: number
  }
  filtros: {
    fecha?: string
    tipo_documento?: string
    sincronizado?: string
    placa?: string
  }
  stats: {
    total_hoy: number
    sin_sincronizar: number
    aceptados_hoy: number
  }
}

const props = defineProps<Props>()

const filter = (key: string, value: string) => {
  const params = { ...props.filtros, [key]: value }
  router.get('/tickets', params, { preserveState: true })
}

const deleteTicket = async (ticket: any) => {
  const result = await Swal.fire({
    title: '¿Eliminar ticket?',
    text: 'Esta acción borrará el ticket y liberará el asiento.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#e63757',
    cancelButtonColor: '#748194',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
  })

  if (result.isConfirmed) {
    router.delete(`/tickets/${ticket.id}`)
  }
}

const formatDate = (dateStr: string) => {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  const pad = (n: number) => n.toString().padStart(2, '0')
  return `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`
}
</script>

<template>
  <AppLayout>
    <Head title="Tickets" />

    <!-- Stats Cards -->
    <div class="row g-3 mb-3">
      <div class="col-md-4">
        <div class="card h-100 border-start border-4 border-primary shadow-none">
          <div class="card-body">
            <h6 class="text-secondary text-uppercase fs--1 mb-1">Total Hoy</h6>
            <div class="fs-4 fw-bold text-dark">{{ stats.total_hoy }}</div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card h-100 border-start border-4 border-warning shadow-none">
          <div class="card-body">
            <h6 class="text-secondary text-uppercase fs--1 mb-1">Sin Sincronizar (Global)</h6>
            <div class="fs-4 fw-bold text-dark">{{ stats.sin_sincronizar }}</div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card h-100 border-start border-4 border-success shadow-none">
          <div class="card-body">
            <h6 class="text-secondary text-uppercase fs--1 mb-1">Aceptados Hoy</h6>
            <div class="fs-4 fw-bold text-dark">{{ stats.aceptados_hoy }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters & Table -->
    <div class="card">
      <div class="card-header bg-light d-flex flex-wrap align-items-center gap-3">
        <!-- Agregar selectores de filtros rápidos aquí -->
        <div>
          <label class="form-label fs--2 text-uppercase mb-1">Sincronizado</label>
          <select @change="(e) => filter('sincronizado', (e.target as HTMLSelectElement).value)" class="form-select form-select-sm">
            <option value="">Todos</option>
            <option value="true" :selected="filtros.sincronizado === 'true'">Sí</option>
            <option value="false" :selected="filtros.sincronizado === 'false'">No</option>
          </select>
        </div>
      </div>

      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-secondary text-uppercase fs--2 font-sans-serif">
              <tr>
                <th class="ps-3 py-3">N° CPE</th>
                <th class="py-3">Pasajero / DNI</th>
                <th class="py-3">Tramo</th>
                <th class="text-center py-3">Asiento</th>
                <th class="text-center py-3">Placa</th>
                <th class="text-end py-3">Monto</th>
                <th class="text-center py-3">Sync</th>
                <th class="py-3">Fecha</th>
                <th class="text-center py-3 pe-3">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="t in tickets.data" :key="t.id">
                <td class="ps-3">
                  <span class="fw-bold text-dark">{{ t.tipo_documento }}</span><br>
                  <span class="text-muted fs--1">{{ t.serie_cpe ? `${t.serie_cpe}-${t.correlativo_cpe}` : 'PENDIENTE' }}</span>
                  <span v-if="t.emitido_en_contingencia" title="Contingencia" class="ms-1">📵</span>
                </td>
                <td>
                  <div class="fw-medium text-dark">{{ t.cliente_nombre }}</div>
                  <div class="text-muted fs--2">{{ t.cliente_doc }}</div>
                </td>
                <td class="fs--1 fw-medium text-secondary">
                  {{ t.origen_tramo }} <i class="fas fa-arrow-right mx-1 text-400"></i> {{ t.destino_tramo }}
                </td>
                <td class="text-center fw-bold text-dark">
                  {{ t.numero_asiento }}
                </td>
                <td class="text-center">
                  <span class="badge border border-secondary text-secondary fw-medium">
                    {{ t.trip?.vehicle?.placa || t.placa_vehiculo }}
                  </span>
                </td>
                <td class="text-end fw-semibold text-success">
                  S/ {{ parseFloat(t.precio).toFixed(2) }}
                </td>
                <td class="text-center fs-2">
                  <span v-if="t.sincronizado" title="Sincronizado" class="text-success"><i class="fas fa-check-circle"></i></span>
                  <span v-else title="No sincronizado" class="text-warning"><i class="fas fa-clock"></i></span>
                </td>
                <td class="fs--1 text-secondary">
                  {{ formatDate(t.emitido_en) }}
                </td>
                <td class="text-center pe-3">
                  <button v-if="!t.trip || t.trip.estado === 'abierto'" class="btn btn-sm btn-link text-danger p-0 shadow-none" title="Eliminar Ticket" @click="deleteTicket(t)">
                    <i class="fas fa-trash"></i>
                  </button>
                  <span v-else class="badge bg-secondary" title="El viaje ya partió o finalizó">Viajando</span>
                </td>
              </tr>
              <tr v-if="tickets.data.length === 0">
                <td colspan="9" class="text-center text-muted py-5">No hay tickets registrados.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
