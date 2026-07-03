<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref, onMounted } from 'vue'

declare const flatpickr: any

interface Trip {
  id: number
  placa_vehiculo: string
  numero_manifiesto: string | null
  fecha_salida: string
  estado: 'abierto' | 'en_ruta' | 'cerrado'
  route: { nombre: string }
  conductor: { name: string }
  tickets_count: number
}

interface Props {
  trips: Trip[]
  fecha: string
}

const props = defineProps<Props>()
const dateRef = ref<HTMLInputElement | null>(null)

onMounted(() => {
  if (dateRef.value) {
    flatpickr(dateRef.value, {
      locale: 'es',
      dateFormat: 'Y-m-d',
      defaultDate: props.fecha,
      onChange: ([date]: Date[]) => {
        if(date) {
            // Need to adjust for timezone differences potentially
            const dateStr = date.toLocaleDateString('en-CA') // YYYY-MM-DD
            router.get('/trips', { fecha: dateStr }, { preserveState: true })
        }
      }
    })
  }
})

const estadoBadge: Record<string, string> = {
  abierto: 'bg-primary',
  en_ruta: 'bg-success',
  cerrado: 'bg-secondary',
}

function formatTime(d: string) {
  return new Date(d).toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' })
}
</script>

<template>
  <AppLayout>
      <Head title="Viajes" />

    <!-- Selector fecha -->
    <div class="card mb-4">
      <div class="card-body py-3 d-flex align-items-center gap-3">
        <label class="form-label mb-0 fw-medium">Fecha:</label>
        <div style="max-width: 200px;">
          <input type="text" ref="dateRef" class="form-control" placeholder="Seleccionar fecha" />
        </div>
        <span class="text-muted small ms-2">{{ trips.length }} viaje(s) encontrados</span>
      </div>
    </div>

    <!-- Tabla -->
    <div class="card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Manifiesto</th>
                <th>Placa</th>
                <th>Ruta</th>
                <th>Conductor</th>
                <th>Salida</th>
                <th class="text-center">Estado</th>
                <th class="text-center">Tickets</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="t in trips" :key="t.id">
                <td class="font-monospace small text-muted">{{ t.numero_manifiesto ?? '—' }}</td>
                <td class="font-monospace fw-bold">{{ t.placa_vehiculo }}</td>
                <td>{{ t.route.nombre }}</td>
                <td>{{ t.conductor.name }}</td>
                <td>{{ formatTime(t.fecha_salida) }}</td>
                <td class="text-center">
                  <span :class="['badge', estadoBadge[t.estado]]">
                    {{ t.estado }}
                  </span>
                </td>
                <td class="text-center fw-medium">{{ t.tickets_count }}</td>
              </tr>
              <tr v-if="trips.length === 0">
                <td colspan="7" class="text-center text-muted py-5">
                  <i class="fas fa-calendar-times fs-2 d-block mb-2"></i>
                  No hay viajes para esta fecha.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    </AppLayout>
</template>
