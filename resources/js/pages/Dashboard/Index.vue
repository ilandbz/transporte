<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'

interface Props {
  stats: {
    viajes_hoy: number
    tickets_hoy: number
    ingresos_hoy: number
    tickets_pendientes_sync: number
    cpe_rechazados: number
  }
  viajes_activos: Array<{
    id: number
    placa_vehiculo: string
    estado: string
    fecha_salida: string
    asientos_ocupados: number[]
    route: { nombre: string }
    conductor: { name: string }
    vehicle: { capacidad_asientos: number }
  }>
}

const props = defineProps<Props>()

function formatTime(d: string) {
  return new Date(d).toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' })
}

function formatMoney(v: number) {
  return `S/ ${Number(v).toFixed(2)}`
}

const estadoBadge: Record<string, string> = {
  abierto:  'bg-primary',
  en_ruta:  'bg-success',
  cerrado:  'bg-secondary',
}
</script>

<template>
  <AppLayout title="Dashboard">

    <!-- Alerta sync -->
    <div v-if="stats.tickets_pendientes_sync > 0"
      class="alert alert-warning d-flex align-items-center gap-2 mb-4" role="alert">
      <i class="fas fa-exclamation-triangle" data-allow-mismatch></i>
      <span>
        Hay <strong>{{ stats.tickets_pendientes_sync }}</strong> ticket(s) pendientes de sincronizar desde los conductores.
        <Link href="/billing/sync" class="alert-link ms-1">Ver detalle →</Link>
      </span>
    </div>

    <!-- KPI Cards -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-xl">
        <div class="card h-100">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="rounded-circle bg-success bg-opacity-10 p-3">
              <i class="fas fa-route text-success fs-5" data-allow-mismatch></i>
            </div>
            <div>
              <p class="text-muted mb-0" style="font-size: 0.75rem;">VIAJES HOY</p>
              <h3 class="mb-0 fw-bold">{{ stats.viajes_hoy }}</h3>
            </div>
          </div>
        </div>
      </div>
      <div class="col-6 col-xl">
        <div class="card h-100">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="rounded-circle bg-info bg-opacity-10 p-3">
              <i class="fas fa-ticket-alt text-info fs-5" data-allow-mismatch></i>
            </div>
            <div>
              <p class="text-muted mb-0" style="font-size: 0.75rem;">TICKETS HOY</p>
              <h3 class="mb-0 fw-bold">{{ stats.tickets_hoy }}</h3>
            </div>
          </div>
        </div>
      </div>
      <div class="col-6 col-xl">
        <div class="card h-100">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="rounded-circle bg-success bg-opacity-10 p-3">
              <i class="fas fa-cash-register text-success fs-5" data-allow-mismatch></i>
            </div>
            <div>
              <p class="text-muted mb-0" style="font-size: 0.75rem;">INGRESOS HOY</p>
              <h3 class="mb-0 fw-bold text-success">{{ formatMoney(stats.ingresos_hoy) }}</h3>
            </div>
          </div>
        </div>
      </div>
      <div class="col-6 col-xl">
        <div :class="['card h-100', stats.tickets_pendientes_sync > 0 ? 'border-warning' : '']">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="rounded-circle bg-warning bg-opacity-10 p-3">
              <i class="fas fa-sync-alt text-warning fs-5" data-allow-mismatch></i>
            </div>
            <div>
              <p class="text-muted mb-0" style="font-size: 0.75rem;">SYNC PENDIENTES</p>
              <h3 :class="['mb-0 fw-bold', stats.tickets_pendientes_sync > 0 ? 'text-warning' : '']">
                {{ stats.tickets_pendientes_sync }}
              </h3>
            </div>
          </div>
        </div>
      </div>
      <div class="col-6 col-xl">
        <div :class="['card h-100', stats.cpe_rechazados > 0 ? 'border-danger' : '']">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="rounded-circle bg-danger bg-opacity-10 p-3">
              <i class="fas fa-times-circle text-danger fs-5" data-allow-mismatch></i>
            </div>
            <div>
              <p class="text-muted mb-0" style="font-size: 0.75rem;">CPE RECHAZADOS</p>
              <h3 :class="['mb-0 fw-bold', stats.cpe_rechazados > 0 ? 'text-danger' : '']">
                {{ stats.cpe_rechazados }}
              </h3>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabla viajes activos -->
    <div class="card">
      <div class="card-header">
        <h6 class="mb-0"><i class="fas fa-bus me-2 text-success" data-allow-mismatch></i>Viajes activos hoy</h6>
      </div>
      <div class="card-body p-0">
        <div v-if="viajes_activos.length === 0" class="text-center text-muted py-5">
          <i class="fas fa-inbox fs-2 d-block mb-2" data-allow-mismatch></i>
          No hay viajes activos en este momento.
        </div>
        <div v-else class="table-responsive">
          <table class="table table-hover table-sm mb-0 align-middle">
            <thead class="table-light">
              <tr>
                <th>Placa</th>
                <th>Ruta</th>
                <th>Conductor</th>
                <th>Estado</th>
                <th>Asientos</th>
                <th>Salida</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="v in viajes_activos" :key="v.id">
                <td><span class="badge bg-dark font-monospace">{{ v.placa_vehiculo }}</span></td>
                <td>{{ v.route.nombre }}</td>
                <td>{{ v.conductor.name }}</td>
                <td>
                  <span :class="['badge', estadoBadge[v.estado] ?? 'bg-secondary']">
                    {{ v.estado }}
                  </span>
                </td>
                <td>
                  <span class="text-muted">
                    {{ v.asientos_ocupados.length }} / {{ v.vehicle?.capacidad_asientos }}
                  </span>
                </td>
                <td class="text-muted">{{ formatTime(v.fecha_salida) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </AppLayout>
</template>
