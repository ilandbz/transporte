<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { ref, onMounted, watch } from 'vue'
import { useForm, router } from '@inertiajs/vue3'

declare const bootstrap: any

interface Vehicle {
  id: number
  placa: string
  marca: string | null
  modelo: string | null
  tipo: string
  capacidad_asientos: number
  activo: boolean
  layout_asientos?: any
}

interface Props {
  vehicles: Vehicle[]
}

const props = defineProps<Props>()

const modalRef = ref<HTMLElement | null>(null)
let bsModal: any = null
const editingVehicle = ref<Vehicle | null>(null)

const form = useForm({
  placa: '',
  marca: '',
  modelo: '',
  tipo: 'minivan',
  capacidad_asientos: 10,
  activo: true,
  layout_asientos: null as any,
})

const layoutEditor = ref(false)
const layout = ref({
  filas: 5,
  columnas: 2,
  pasillo: null as number | null,
  asientos: [] as { numero: number | null, clase: string }[]
})

function initLayoutFromForm() {
  if (editingVehicle.value?.layout_asientos) {
    let saved = editingVehicle.value.layout_asientos
    if (typeof saved === 'string') saved = JSON.parse(saved)
    layout.value = JSON.parse(JSON.stringify(saved))
  } else {
    const tipo = form.tipo
    const cols = tipo === 'minivan' ? 2 : (tipo === 'auto' ? 3 : 4)
    const p = (tipo === 'minivan' || tipo === 'auto') ? null : 2
    const rows = Math.ceil(form.capacidad_asientos / cols)
    
    layout.value.filas = rows || 1
    layout.value.columnas = cols
    layout.value.pasillo = p
    layout.value.asientos = []
    
    for (let i = 0; i < layout.value.filas * layout.value.columnas; i++) {
      layout.value.asientos.push({ numero: null, clase: 'normal' })
    }
  }
  recalculateSeats()
}

function resizeGrid() {
  const total = layout.value.filas * layout.value.columnas
  if (layout.value.asientos.length < total) {
    for (let i = layout.value.asientos.length; i < total; i++) {
      layout.value.asientos.push({ numero: null, clase: 'normal' })
    }
  } else if (layout.value.asientos.length > total) {
    layout.value.asientos.splice(total)
  }
  recalculateSeats()
}

function toggleSeat(index: number) {
  const seat = layout.value.asientos[index]
  if (seat.clase === 'normal') seat.clase = 'vip'
  else if (seat.clase === 'vip') seat.clase = 'empty'
  else seat.clase = 'normal'
  
  recalculateSeats()
}

function recalculateSeats() {
  let counter = 1
  layout.value.asientos.forEach(seat => {
    if (seat.clase !== 'empty') {
      seat.numero = counter++
    } else {
      seat.numero = null
    }
  })
  form.capacidad_asientos = counter - 1
  form.layout_asientos = layout.value
}

watch(() => layout.value.filas, resizeGrid)
watch(() => layout.value.columnas, resizeGrid)

onMounted(() => {
  if (modalRef.value) {
    bsModal = new bootstrap.Modal(modalRef.value)
  }
})

function openCreate() {
  editingVehicle.value = null
  form.reset()
  form.tipo = 'minivan'
  form.capacidad_asientos = 10
  form.layout_asientos = null
  layoutEditor.value = false
  form.clearErrors()
  initLayoutFromForm()
  bsModal?.show()
}

function openEdit(v: Vehicle) {
  editingVehicle.value = v
  form.placa = v.placa
  form.marca = v.marca ?? ''
  form.modelo = v.modelo ?? ''
  form.tipo = v.tipo
  form.capacidad_asientos = v.capacidad_asientos
  form.activo = v.activo
  form.layout_asientos = null
  layoutEditor.value = false
  form.clearErrors()
  initLayoutFromForm()
  bsModal?.show()
}

function closeModal() {
  bsModal?.hide()
  form.reset()
}

function submit() {
  if (editingVehicle.value) {
    form.put(`/settings/vehicles/${editingVehicle.value.id}`, {
      onSuccess: () => closeModal(),
    })
  } else {
    form.post('/settings/vehicles', {
      onSuccess: () => closeModal(),
    })
  }
}

function toggleActivo(v: Vehicle) {
  router.patch(`/settings/vehicles/${v.id}/toggle`)
}

const tipoBadge: Record<string, string> = {
  minivan: 'bg-primary',
  coaster: 'bg-success',
  bus:     'bg-warning text-dark',
  auto:    'bg-info text-dark',
}
</script>

<template>
  <AppLayout>
  
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
      <div>
        <h5 class="mb-0 fw-semibold">Flota de vehículos</h5>
        <p class="text-muted mb-0 small">{{ vehicles.length }} vehículos registrados</p>
      </div>
      <button class="btn btn-success" @click="openCreate">
        <i class="fas fa-plus me-1"></i> Nuevo vehículo
      </button>
    </div>

    <!-- Tabla -->
    <div class="card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Placa</th>
                <th>Marca / Modelo</th>
                <th>Tipo</th>
                <th class="text-center">Asientos</th>
                <th class="text-center">Estado</th>
                <th class="text-center">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="v in vehicles" :key="v.id" :class="[!v.activo && 'opacity-50']">
                <td class="font-monospace fw-bold">{{ v.placa }}</td>
                <td class="text-muted">
                  {{ [v.marca, v.modelo].filter(Boolean).join(' ') || '—' }}
                </td>
                <td>
                  <span :class="['badge', tipoBadge[v.tipo] ?? 'bg-secondary']">
                    {{ v.tipo }}
                  </span>
                </td>
                <td class="text-center fw-medium">{{ v.capacidad_asientos }}</td>
                <td class="text-center">
                  <div class="form-check form-switch d-inline-block m-0">
                    <input class="form-check-input" type="checkbox" :checked="v.activo" @change="toggleActivo(v)">
                  </div>
                </td>
                <td class="text-center">
                  <button class="btn btn-sm btn-outline-primary" @click="openEdit(v)">
                    <i class="fas fa-edit"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" ref="modalRef" tabindex="-1" data-bs-backdrop="static">
      <div class="modal-dialog" :class="{ 'modal-lg': layoutEditor }">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              {{ editingVehicle ? 'Editar vehículo' : 'Nuevo vehículo' }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Placa</label>
              <input v-model="form.placa" type="text" class="form-control text-uppercase"
                :class="{ 'is-invalid': form.errors.placa }" placeholder="ABC-123" />
              <div v-if="form.errors.placa" class="invalid-feedback">{{ form.errors.placa }}</div>
            </div>
            <div class="row g-3 mb-3">
              <div class="col-6">
                <label class="form-label">Marca</label>
                <input v-model="form.marca" type="text" class="form-control" placeholder="Toyota" />
              </div>
              <div class="col-6">
                <label class="form-label">Modelo</label>
                <input v-model="form.modelo" type="text" class="form-control" placeholder="Hiace" />
              </div>
            </div>
            <div class="row g-3">
              <div class="col-6">
                <label class="form-label">Tipo</label>
                <select v-model="form.tipo" class="form-select">
                  <option value="auto">Auto</option>
                  <option value="minivan">Minivan</option>
                  <option value="coaster">Coaster</option>
                  <option value="bus">Bus</option>
                </select>
              </div>
              <div class="col-6">
                <label class="form-label">N° asientos</label>
                <input v-model.number="form.capacidad_asientos" type="number" min="1" max="60"
                  class="form-control" :class="{ 'is-invalid': form.errors.capacidad_asientos }" readonly />
                <div class="form-text">Calculado por el layout.</div>
                <div v-if="form.errors.capacidad_asientos" class="invalid-feedback">{{ form.errors.capacidad_asientos }}</div>
              </div>
            </div>

            <div class="mt-4 pt-3 border-top">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-couch me-2"></i> Configuración de Asientos</h6>
                <button type="button" class="btn btn-sm btn-outline-secondary" @click="layoutEditor = !layoutEditor">
                  {{ layoutEditor ? 'Ocultar Layout' : 'Configurar Layout' }}
                </button>
              </div>

              <div v-if="layoutEditor" class="bg-light p-3 rounded border">
                <div class="row g-2 mb-4">
                  <div class="col-4">
                    <label class="form-label small text-muted mb-1">Filas</label>
                    <input type="number" class="form-control form-control-sm" v-model.number="layout.filas" min="1" max="25">
                  </div>
                  <div class="col-4">
                    <label class="form-label small text-muted mb-1">Columnas</label>
                    <input type="number" class="form-control form-control-sm" v-model.number="layout.columnas" min="1" max="6">
                  </div>
                  <div class="col-4">
                    <label class="form-label small text-muted mb-1">Pasillo (Col)</label>
                    <input type="number" class="form-control form-control-sm" v-model.number="layout.pasillo" min="0" :max="layout.columnas" placeholder="Ej: 2">
                  </div>
                </div>

                <div class="d-flex justify-content-center mb-3 gap-2 small">
                  <span class="badge bg-white text-dark border"><i class="fas fa-couch"></i> Normal</span>
                  <span class="badge bg-white text-primary border"><i class="fas fa-couch"></i> VIP</span>
                  <span class="badge bg-white text-muted border border-dashed"><i class="fas fa-times"></i> Vacío</span>
                </div>

                <div class="d-flex justify-content-center">
                  <div class="seat-container border rounded bg-white p-4 shadow-sm" style="max-width: 100%; position: relative;">
                    <div class="d-flex justify-content-end mb-4 border-bottom pb-2">
                      <div class="text-center text-muted fw-bold small">
                        FRENTE <i class="fas fa-steering-wheel ms-1"></i>
                      </div>
                    </div>
                    <div class="d-grid gap-3" 
                         :style="{
                           'grid-template-columns': `repeat(${layout.columnas + (layout.pasillo ? 1 : 0)}, auto)`,
                           'justify-content': 'center'
                         }">
                      <template v-for="(seat, index) in layout.asientos" :key="index">
                        
                        <div v-if="layout.pasillo && (index % layout.columnas) === layout.pasillo" 
                             class="d-flex align-items-center justify-content-center" 
                             style="width: 30px;">
                        </div>

                        <button type="button" class="btn btn-sm d-flex flex-column align-items-center justify-content-center shadow-none border"
                                :class="{
                                  'bg-light text-dark': seat.clase === 'normal',
                                  'bg-light text-primary border-primary': seat.clase === 'vip',
                                  'bg-transparent border-dashed text-muted': seat.clase === 'empty'
                                }"
                                @click="toggleSeat(index)"
                                style="width: 50px; height: 50px; border-radius: 8px; transition: all 0.2s; border-style: solid;"
                                :style="seat.clase === 'empty' ? 'border-style: dashed !important; opacity: 0.5;' : ''">
                          <i v-if="seat.clase !== 'empty'" class="fas fa-couch mb-1" style="font-size: 1.2rem;"></i>
                          <i v-else class="fas fa-times mb-1"></i>
                          <span v-if="seat.numero" style="font-size: 0.75rem; font-weight: bold; line-height: 1;">{{ seat.numero }}</span>
                        </button>
                        
                      </template>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button class="btn btn-success" @click="submit" :disabled="form.processing">
              <span v-if="form.processing">
                <i class="fas fa-spinner fa-spin me-1"></i> Guardando...
              </span>
              <span v-else>
                <i class="fas fa-save me-1"></i>
                {{ editingVehicle ? 'Actualizar' : 'Registrar' }}
              </span>
            </button>
          </div>
        </div>
      </div>
    </div>

    </AppLayout>
</template>
