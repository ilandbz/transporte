<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'

declare const bootstrap: any
declare const Swal: any

const props = defineProps<{
  trips: any[]
}>()

const modalRef = ref<HTMLElement | null>(null)
let modalInstance: any = null

const packageForm = useForm({
  trip_id: '',
  remitente_nombre: '',
  remitente_dni: '',
  remitente_telefono: '',
  destinatario_nombre: '',
  destinatario_dni: '',
  destinatario_telefono: '',
  descripcion: '',
  peso_kg: '',
  precio: '',
  estado_pago: 'pagado'
})

onMounted(() => {
  if (modalRef.value) {
    modalInstance = new bootstrap.Modal(modalRef.value)
  }
})

// Metodo expuesto para abrir el modal desde el componente padre
const show = (preselectedTripId?: string | number) => {
  packageForm.reset()
  packageForm.clearErrors()
  
  if (preselectedTripId) {
    packageForm.trip_id = preselectedTripId.toString()
  }
  
  if (modalInstance) {
    modalInstance.show()
  }
}

// Exponer el metodo show para que pueda ser llamado mediante un ref
defineExpose({
  show
})

async function buscarRemitente() {
  const dni = packageForm.remitente_dni
  if (!dni || (dni.length !== 8 && dni.length !== 11)) return
  try {
    const res = await fetch(`/clientes/search/${dni}`)
    if (res.ok) {
      const data = await res.json()
      if (data.encontrado) {
        packageForm.remitente_nombre = data.datos.nombre || ''
        if (data.datos.telefono) packageForm.remitente_telefono = data.datos.telefono
      }
    }
  } catch (error) {}
}

async function buscarDestinatario() {
  const dni = packageForm.destinatario_dni
  if (!dni || (dni.length !== 8 && dni.length !== 11)) return
  try {
    const res = await fetch(`/clientes/search/${dni}`)
    if (res.ok) {
      const data = await res.json()
      if (data.encontrado) {
        packageForm.destinatario_nombre = data.datos.nombre || ''
        if (data.datos.telefono) packageForm.destinatario_telefono = data.datos.telefono
      }
    }
  } catch (error) {}
}

const submitPackage = () => {
  packageForm.post('/packages', {
    onSuccess: () => {
      if (modalInstance) {
        modalInstance.hide()
      }
      Swal.fire('¡Éxito!', 'Encomienda registrada correctamente', 'success')
    },
    onError: (errors) => {
      let msg = 'Verifica los datos ingresados.'
      if (Object.values(errors).length > 0) {
        msg = Object.values(errors)[0] as string
      }
      Swal.fire('Error', msg, 'error')
    }
  })
}
</script>

<template>
  <div class="modal fade" tabindex="-1" aria-hidden="true" ref="modalRef">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form @submit.prevent="submitPackage">
          <div class="modal-header">
            <h5 class="modal-title"><i class="fas fa-box me-2"></i>Registrar Nueva Encomienda</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            
            <div class="mb-4">
              <label class="form-label fw-bold">Seleccionar Viaje (Bus)</label>
              <select v-model="packageForm.trip_id" class="form-select" :class="{ 'is-invalid': packageForm.errors.trip_id }" required>
                <option value="" disabled>Seleccionar viaje disponible...</option>
                <option v-for="t in trips" :key="t.id" :value="t.id">
                  {{ t.route?.nombre }} | Placa: {{ t.vehicle?.placa || t.placa_vehiculo }}
                </option>
              </select>
              <div class="invalid-feedback">{{ packageForm.errors.trip_id }}</div>
            </div>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label text-primary fw-medium"><i class="fas fa-user me-1"></i>Remitente</label>
                <div class="card shadow-none border p-3">
                  <div class="mb-2">
                    <label class="form-label fs--1 mb-1">Nombre Completo *</label>
                    <input type="text" v-model="packageForm.remitente_nombre" class="form-control form-control-sm" :class="{ 'is-invalid': packageForm.errors.remitente_nombre }" required>
                    <div class="invalid-feedback">{{ packageForm.errors.remitente_nombre }}</div>
                  </div>
                  <div class="row g-2">
                    <div class="col-6">
                      <label class="form-label fs--1 mb-1">DNI/RUC (Opcional)</label>
                      <input type="text" v-model="packageForm.remitente_dni" @blur="buscarRemitente" class="form-control form-control-sm" :class="{ 'is-invalid': packageForm.errors.remitente_dni }" maxlength="15">
                    </div>
                    <div class="col-6">
                      <label class="form-label fs--1 mb-1">Teléfono (Opcional)</label>
                      <input type="text" v-model="packageForm.remitente_telefono" class="form-control form-control-sm" :class="{ 'is-invalid': packageForm.errors.remitente_telefono }">
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-md-6 mb-3">
                <label class="form-label text-success fw-medium"><i class="fas fa-user-check me-1"></i>Destinatario</label>
                <div class="card shadow-none border p-3">
                  <div class="mb-2">
                    <label class="form-label fs--1 mb-1">Nombre Completo *</label>
                    <input type="text" v-model="packageForm.destinatario_nombre" class="form-control form-control-sm" :class="{ 'is-invalid': packageForm.errors.destinatario_nombre }" required>
                    <div class="invalid-feedback">{{ packageForm.errors.destinatario_nombre }}</div>
                  </div>
                  <div class="row g-2">
                    <div class="col-6">
                      <label class="form-label fs--1 mb-1">DNI/RUC (Opcional)</label>
                      <input type="text" v-model="packageForm.destinatario_dni" @blur="buscarDestinatario" class="form-control form-control-sm" :class="{ 'is-invalid': packageForm.errors.destinatario_dni }" maxlength="15">
                    </div>
                    <div class="col-6">
                      <label class="form-label fs--1 mb-1">Teléfono (Opcional)</label>
                      <input type="text" v-model="packageForm.destinatario_telefono" class="form-control form-control-sm" :class="{ 'is-invalid': packageForm.errors.destinatario_telefono }">
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <hr>

            <div class="row">
              <div class="col-md-12 mb-3">
                <label class="form-label">Descripción del Bulto *</label>
                <input type="text" v-model="packageForm.descripcion" class="form-control" :class="{ 'is-invalid': packageForm.errors.descripcion }" placeholder="Ej: 2 Cajas de Ropa" required>
                <div class="invalid-feedback">{{ packageForm.errors.descripcion }}</div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-4 mb-3">
                <label class="form-label">Peso (Kg)</label>
                <input type="number" step="0.1" v-model="packageForm.peso_kg" class="form-control" :class="{ 'is-invalid': packageForm.errors.peso_kg }">
                <div class="invalid-feedback">{{ packageForm.errors.peso_kg }}</div>
              </div>
              <div class="col-md-4 mb-3">
                <label class="form-label">Precio (S/) *</label>
                <input type="number" step="0.1" v-model="packageForm.precio" class="form-control" :class="{ 'is-invalid': packageForm.errors.precio }" required>
                <div class="invalid-feedback">{{ packageForm.errors.precio }}</div>
              </div>
              <div class="col-md-4 mb-3">
                <label class="form-label">Estado de Pago *</label>
                <select v-model="packageForm.estado_pago" class="form-select" :class="{ 'is-invalid': packageForm.errors.estado_pago }" required>
                  <option value="pagado">Pagado</option>
                  <option value="por_cobrar">Por Cobrar (Contra entrega)</option>
                </select>
                <div class="invalid-feedback">{{ packageForm.errors.estado_pago }}</div>
              </div>
            </div>

          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary" :disabled="packageForm.processing">
              <i class="fas fa-save me-1"></i> Guardar Encomienda
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
