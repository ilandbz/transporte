<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref } from 'vue'

interface Props {
  packages: {
    data: any[]
    links: any[]
    current_page: number
  }
  filtros: {
    estado?: string
    fecha?: string
  }
  stats: {
    en_transito: number
    entregados: number
    pendientes: number
  }
  trips: any[]
}

const props = defineProps<Props>()

import PackageFormModal from '@/components/PackageFormModal.vue'
const packageModal = ref<InstanceType<typeof PackageFormModal> | null>(null)

const filter = (key: string, value: string) => {
  const params = { ...props.filtros, [key]: value }
  router.get('/packages', params, { preserveState: true })
}

const getEstadoBadge = (estado: string) => {
  if (estado === 'en_transito') return { text: 'En Tránsito', class: 'bg-primary' }
  if (estado === 'entregado') return { text: 'Entregado', class: 'bg-success' }
  return { text: 'Pendiente', class: 'bg-secondary' }
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
    <Head title="Encomiendas" />

    <!-- Stats Cards -->
    <div class="row g-3 mb-3">
      <div class="col-md-4">
        <div class="card h-100 border-start border-4 border-primary shadow-none">
          <div class="card-body">
            <h6 class="text-secondary text-uppercase fs--1 mb-1">En Tránsito</h6>
            <div class="fs-4 fw-bold text-dark">{{ stats.en_transito }}</div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card h-100 border-start border-4 border-success shadow-none">
          <div class="card-body">
            <h6 class="text-secondary text-uppercase fs--1 mb-1">Entregados</h6>
            <div class="fs-4 fw-bold text-dark">{{ stats.entregados }}</div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card h-100 border-start border-4 border-secondary shadow-none">
          <div class="card-body">
            <h6 class="text-secondary text-uppercase fs--1 mb-1">Pendientes</h6>
            <div class="fs-4 fw-bold text-dark">{{ stats.pendientes }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters & Table -->
    <div class="card">
      <div class="card-header bg-light d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
          <label class="form-label fs--2 text-uppercase mb-1">Estado</label>
          <select @change="(e) => filter('estado', (e.target as HTMLSelectElement).value)" class="form-select form-select-sm">
            <option value="">Todos</option>
            <option value="pendiente" :selected="filtros.estado === 'pendiente'">Pendiente</option>
            <option value="en_transito" :selected="filtros.estado === 'en_transito'">En Tránsito</option>
            <option value="entregado" :selected="filtros.estado === 'entregado'">Entregado</option>
          </select>
        </div>
        <div>
          <button @click="packageModal?.show()" class="btn btn-primary btn-sm">
            <i class="fas fa-plus me-1"></i> Nueva Encomienda
          </button>
        </div>
      </div>

      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-secondary text-uppercase fs--2 font-sans-serif">
              <tr>
                <th class="ps-3 py-3">Código QR</th>
                <th class="py-3">Remitente</th>
                <th class="py-3">Destinatario</th>
                <th class="py-3">Descripción</th>
                <th class="text-center py-3">Peso</th>
                <th class="text-end py-3">Precio</th>
                <th class="text-center py-3">Estado</th>
                <th class="text-center py-3">Pago</th>
                <th class="text-center py-3">Placa</th>
                <th class="py-3 pe-3">Fecha</th>
                <th class="text-end py-3 pe-3">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in packages.data" :key="p.id">
                <td class="ps-3">
                  <span class="badge border border-primary text-primary fw-bold font-monospace">
                    {{ p.codigo_qr ? p.codigo_qr.substring(0,8) : 'N/A' }}
                  </span>
                </td>
                <td>
                  <div class="fw-medium text-dark">{{ p.remitente_nombre }}</div>
                  <div class="text-muted fs--2">{{ p.remitente_doc }}</div>
                </td>
                <td>
                  <div class="fw-medium text-dark">{{ p.destinatario_nombre }}</div>
                  <div class="text-muted fs--2">{{ p.destinatario_doc }}</div>
                </td>
                <td class="fs--1 text-secondary text-truncate" style="max-width: 150px;" :title="p.descripcion">
                  {{ p.descripcion }}
                </td>
                <td class="text-center fw-medium text-dark">
                  {{ parseFloat(p.peso).toFixed(1) }} <span class="text-muted fs--2">kg</span>
                </td>
                <td class="text-end fw-semibold text-success">
                  S/ {{ parseFloat(p.precio).toFixed(2) }}
                </td>
                <td class="text-center">
                  <span class="badge" :class="getEstadoBadge(p.estado).class">
                    {{ getEstadoBadge(p.estado).text }}
                  </span>
                </td>
                <td class="text-center">
                  <span v-if="p.estado_pago === 'pagado'" class="badge bg-success-subtle text-success">Pagado</span>
                  <span v-else class="badge bg-danger-subtle text-danger">Por Cobrar</span>
                </td>
                <td class="text-center">
                  <span class="badge border border-secondary text-secondary fw-medium">
                    {{ p.trip?.vehicle?.placa || 'N/A' }}
                  </span>
                </td>
                <td class="fs--1 text-secondary pe-3">
                  {{ formatDate(p.created_at) }}
                </td>
                <td class="text-end pe-3">
                  <div class="dropdown font-sans-serif position-static">
                    <button class="btn btn-link text-600 btn-sm dropdown-toggle btn-reveal" type="button" data-bs-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false">
                      <span class="fas fa-print fs--1"></span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end border py-0">
                      <div class="bg-white py-2">
                        <a class="dropdown-item" :href="`/packages/${p.id}/print?format=80mm`" target="_blank">Ticket (80mm)</a>
                        <a class="dropdown-item" :href="`/packages/${p.id}/print?format=a4`" target="_blank">Formato A4/A5</a>
                      </div>
                    </div>
                  </div>
                </td>
              </tr>
              <tr v-if="packages.data.length === 0">
                <td colspan="11" class="text-center text-muted py-5">No hay encomiendas registradas.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>

  <PackageFormModal ref="packageModal" :trips="trips" />
</template>
