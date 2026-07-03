<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { ref, onMounted } from 'vue'
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
})

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
  form.clearErrors()
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
  form.clearErrors()
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
    <div class="modal fade" ref="modalRef" tabindex="-1">
      <div class="modal-dialog">
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
                  <option value="minivan">Minivan</option>
                  <option value="coaster">Coaster</option>
                  <option value="bus">Bus</option>
                </select>
              </div>
              <div class="col-6">
                <label class="form-label">N° asientos</label>
                <input v-model.number="form.capacidad_asientos" type="number" min="1" max="60"
                  class="form-control" :class="{ 'is-invalid': form.errors.capacidad_asientos }" />
                <div v-if="form.errors.capacidad_asientos" class="invalid-feedback">{{ form.errors.capacidad_asientos }}</div>
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
