<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import axios from 'axios'

const emit = defineEmits(['success'])

const modalRef = ref<HTMLElement | null>(null)
let bsModal: any = null

onMounted(() => {
  if (modalRef.value) {
    bsModal = new (window as any).bootstrap.Modal(modalRef.value)
  }
})

const form = useForm({
  ticket_id: null as number | null,
  tipo_documento: 'BOLETA',
  documento_facturacion: '',
  nombre_facturacion: '',
  telefono_pasajero: ''
})

const show = (ticket: any) => {
  form.reset()
  form.clearErrors()
  
  form.ticket_id = ticket.id
  form.tipo_documento = 'BOLETA'
  form.documento_facturacion = ticket.dni_pasajero || ''
  form.nombre_facturacion = ticket.nombre_pasajero || ''
  form.telefono_pasajero = ticket.telefono_pasajero || ''
  
  bsModal?.show()
}

const hide = () => {
  bsModal?.hide()
}

async function buscarCliente() {
  const doc = form.documento_facturacion
  if (!doc) return
  
  try {
    const res = await fetch(`/clientes/search/${doc}`)
    if (res.ok) {
      const data = await res.json()
      if (data.encontrado) {
        form.nombre_facturacion = data.datos.nombre || ''
      }
    }
  } catch (error) {
    // console.log("Not found")
  }
}

const submit = () => {
  form.post(`/tickets/${form.ticket_id}/convert-cpe`, {
    onSuccess: () => {
      hide()
      emit('success')
    },
    onError: (errors) => {
      let msg = 'Verifica los datos ingresados.'
      if (errors.message) {
        msg = errors.message
      } else if (Object.values(errors).length > 0) {
        msg = Object.values(errors)[0] as string
      }
      ;(window as any).Swal.fire('Error', msg, 'error')
    }
  })
}

defineExpose({ show, hide })
</script>

<template>
  <div class="modal fade" id="convertCpeModal" tabindex="-1" aria-hidden="true" ref="modalRef">
    <div class="modal-dialog">
      <div class="modal-content">
        <form @submit.prevent="submit">
          <div class="modal-header">
            <h5 class="modal-title">Convertir a Comprobante Electrónico</h5>
            <button type="button" class="btn-close" @click="hide" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="alert alert-info py-2 fs--1 mb-3">
              Estás a punto de emitir una Boleta o Factura para este Ticket Interno.
            </div>
            
            <div class="mb-3">
              <label class="form-label">Tipo de Documento</label>
              <select v-model="form.tipo_documento" class="form-select" :class="{ 'is-invalid': form.errors.tipo_documento }">
                <option value="BOLETA">Boleta Electrónica</option>
                <option value="FACTURA">Factura Electrónica</option>
              </select>
              <div class="invalid-feedback">{{ form.errors.tipo_documento }}</div>
            </div>

            <div class="mb-3">
              <label class="form-label">{{ form.tipo_documento === 'FACTURA' ? 'RUC Cliente' : 'DNI/Doc Cliente' }}</label>
              <input type="text" v-model="form.documento_facturacion" @blur="buscarCliente" class="form-control" :class="{ 'is-invalid': form.errors.documento_facturacion }" :maxlength="form.tipo_documento === 'FACTURA' ? 11 : 15" required>
              <div class="invalid-feedback">{{ form.errors.documento_facturacion }}</div>
            </div>

            <div class="mb-3">
              <label class="form-label">Nombre / Razón Social</label>
              <input type="text" v-model="form.nombre_facturacion" class="form-control" :class="{ 'is-invalid': form.errors.nombre_facturacion }" required>
              <div class="invalid-feedback">{{ form.errors.nombre_facturacion }}</div>
            </div>

            <div class="mb-3">
              <label class="form-label">Teléfono Pasajero (Opcional)</label>
              <input type="text" v-model="form.telefono_pasajero" class="form-control" :class="{ 'is-invalid': form.errors.telefono_pasajero }">
              <div class="invalid-feedback">{{ form.errors.telefono_pasajero }}</div>
            </div>
            
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="hide">Cancelar</button>
            <button type="submit" class="btn btn-primary" :disabled="form.processing">
              <i class="fas fa-file-invoice me-1"></i> Emitir CPE
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
