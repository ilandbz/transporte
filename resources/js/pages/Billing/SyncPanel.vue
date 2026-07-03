<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'

interface Ticket {
  id: number
  uuid_local: string
  origen_tramo: string
  destino_tramo: string
  precio: string
  emitido_en: string
}

interface ConductorData {
  conductor_id: number
  conductor_nombre: string
  conductor_email: string
  cantidad: number
  monto_total: number
  mas_antiguo: string
  mas_reciente: string
  tickets: Ticket[]
}

interface Props {
  pendientes: ConductorData[]
  total_pendientes: number
  monto_total: number
}

const props = defineProps<Props>()
const expandedConductor = ref<number | null>(null)

const toggleExpand = (id: number) => {
  expandedConductor.value = expandedConductor.value === id ? null : id
}

const isAntiguo = (dateStr: string) => {
  const ticketDate = new Date(dateStr)
  const limiteDate = new Date()
  limiteDate.setDate(limiteDate.getDate() - 7)
  return ticketDate < limiteDate
}

const formatDate = (dateStr: string) => {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  const pad = (n: number) => n.toString().padStart(2, '0')
  return `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()}`
}
</script>

<template>
  <AppLayout>
    <Head title="Sync Panel" />
    
    <!-- Banner Alerta General -->
    <div v-if="total_pendientes > 0" class="alert alert-danger d-flex flex-column flex-md-row align-items-center justify-content-between mb-4 border-0 shadow-sm p-4">
      <div class="d-flex align-items-center mb-3 mb-md-0">
        <span class="fs-4 me-3"><i class="fas fa-exclamation-triangle"></i></span>
        <div>
          <h4 class="alert-heading fw-bold mb-1">{{ total_pendientes }} tickets pendientes de regularizar ante SUNAT</h4>
          <p class="mb-0 fw-medium">Monto total en riesgo: S/ {{ monto_total.toFixed(2) }}</p>
        </div>
      </div>
      <button class="btn btn-danger shadow-sm fw-semibold">
        Forzar Sincronización Global
      </button>
    </div>
    <div v-else class="alert alert-success d-flex align-items-center mb-4 border-0 shadow-sm p-4">
      <span class="fs-4 me-3"><i class="fas fa-check-circle"></i></span>
      <h4 class="alert-heading fw-bold mb-0">Todos los tickets están sincronizados con SUNAT.</h4>
    </div>

    <!-- Lista de Conductores -->
    <div class="row g-4">
      <div v-for="c in pendientes" :key="c.conductor_id" class="col-md-6 col-lg-4">
        <div class="card h-100 shadow-sm overflow-hidden d-flex flex-column">
          
          <div class="card-body flex-grow-1 border-bottom border-200">
            <div class="d-flex justify-content-between align-items-start mb-4">
              <div>
                <h5 class="fw-bold text-dark mb-0">{{ c.conductor_nombre }}</h5>
                <p class="text-secondary fs--1 mb-0">{{ c.conductor_email }}</p>
              </div>
              <span class="badge rounded-pill bg-warning text-dark fs-1 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                {{ c.cantidad }}
              </span>
            </div>
            
            <div class="mb-2">
              <div class="d-flex justify-content-between fs--1 mb-1">
                <span class="text-secondary">Monto retenido:</span>
                <span class="fw-bold text-dark">S/ {{ c.monto_total.toFixed(2) }}</span>
              </div>
              <div class="d-flex justify-content-between fs--1 mb-1">
                <span class="text-secondary">Ticket más antiguo:</span>
                <span :class="isAntiguo(c.mas_antiguo) ? 'text-danger fw-bold' : 'text-dark fw-medium'">
                  {{ formatDate(c.mas_antiguo) }}
                </span>
              </div>
            </div>
            <div v-if="isAntiguo(c.mas_antiguo)" class="alert alert-warning py-2 px-3 mt-3 mb-0 fs--2 d-flex align-items-start border-0">
              <i class="fas fa-exclamation-circle mt-1 me-2"></i>
              <span>Tiene tickets con más de 7 días, SUNAT podría rechazarlos.</span>
            </div>
          </div>
          
          <div class="card-footer bg-light p-2">
            <button @click="toggleExpand(c.conductor_id)" class="btn btn-sm btn-outline-secondary w-100 fw-medium">
              {{ expandedConductor === c.conductor_id ? 'Ocultar tickets' : 'Ver tickets' }}
            </button>
          </div>

          <!-- Sublista expandible -->
          <div v-if="expandedConductor === c.conductor_id" class="bg-white" style="max-height: 240px; overflow-y: auto;">
            <table class="table table-sm table-striped align-middle fs--2 mb-0">
              <thead class="table-light sticky-top shadow-sm">
                <tr>
                  <th class="ps-3 py-2 text-uppercase text-secondary">Fecha</th>
                  <th class="py-2 text-uppercase text-secondary">Tramo</th>
                  <th class="pe-3 py-2 text-end text-uppercase text-secondary">Precio</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="t in c.tickets" :key="t.id">
                  <td class="ps-3 text-secondary">{{ formatDate(t.emitido_en) }}</td>
                  <td class="fw-medium text-dark">{{ t.origen_tramo }} - {{ t.destino_tramo }}</td>
                  <td class="pe-3 text-end fw-semibold text-success">S/ {{ parseFloat(t.precio).toFixed(2) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
