<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router, useForm, Link } from '@inertiajs/vue3'
import { ref, onMounted, watch } from 'vue'

declare const bootstrap: any
declare const Swal: any

const props = defineProps<{
  clientes: any,
  filters: any
}>()

const search = ref(props.filters.search || '')

watch(search, (value) => {
  router.get('/clientes', { search: value }, { preserveState: true, preserveScroll: true, replace: true })
})

const clientModalRef = ref<HTMLElement | null>(null)
let clientModalInstance: any = null
const isEditing = ref(false)

const clientForm = useForm({
  id: '',
  documento: '',
  nombre: '',
  telefono: '',
  email: ''
})

onMounted(() => {
  if (clientModalRef.value) {
    clientModalInstance = new bootstrap.Modal(clientModalRef.value)
  }
})

const openModal = (cliente?: any) => {
  clientForm.reset()
  clientForm.clearErrors()
  if (cliente && !(cliente instanceof Event)) {
    isEditing.value = true
    clientForm.id = cliente.id
    clientForm.documento = cliente.documento
    clientForm.nombre = cliente.nombre
    clientForm.telefono = cliente.telefono || ''
    clientForm.email = cliente.email || ''
  } else {
    isEditing.value = false
    clientForm.id = ''
    clientForm.documento = ''
    clientForm.nombre = ''
    clientForm.telefono = ''
    clientForm.email = ''
  }
  clientModalInstance?.show()
}

const saveClient = () => {
  if (isEditing.value) {
    clientForm.put(`/clientes/${clientForm.id}`, {
      onSuccess: () => {
        clientModalInstance?.hide()
        Swal.fire('¡Éxito!', 'Cliente actualizado correctamente', 'success')
      }
    })
  } else {
    clientForm.post('/clientes', {
      onSuccess: () => {
        clientModalInstance?.hide()
        Swal.fire('¡Éxito!', 'Cliente creado correctamente', 'success')
      }
    })
  }
}

const deleteClient = (id: number) => {
  Swal.fire({
    title: '¿Estás seguro?',
    text: "Esta acción no se puede deshacer.",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  }).then((result: any) => {
    if (result.isConfirmed) {
      router.delete(`/clientes/${id}`, {
        onSuccess: () => Swal.fire('¡Eliminado!', 'El cliente ha sido eliminado.', 'success')
      })
    }
  })
}
</script>

<template>
  <AppLayout>
    <Head title="Clientes Frecuentes" />
    
    <div class="card mb-3">
      <div class="card-header border-bottom">
        <div class="row flex-between-end">
          <div class="col-auto align-self-center">
            <h5 class="mb-0" data-anchor="data-anchor">Gestión de Clientes</h5>
          </div>
          <div class="col-auto ms-auto">
            <div class="nav nav-pills nav-pills-falcon flex-grow-1" role="tablist">
              <button class="btn btn-sm" @click="openModal()">
                <i class="fas fa-plus me-1"></i> Nuevo Cliente
              </button>
            </div>
          </div>
        </div>
      </div>
      <div class="card-body pt-0">
        <div class="row mt-3 mb-3">
          <div class="col-md-4">
            <div class="input-group input-group-sm">
              <span class="input-group-text"><i class="fas fa-search"></i></span>
              <input type="text" v-model="search" class="form-control" placeholder="Buscar por DNI/RUC o Nombre...">
            </div>
          </div>
        </div>
        <div class="table-responsive scrollbar">
          <table class="table table-sm table-striped fs--1 mb-0 overflow-hidden">
            <thead class="bg-200 text-900">
              <tr>
                <th class="sort pe-1 align-middle white-space-nowrap">DNI/RUC</th>
                <th class="sort pe-1 align-middle white-space-nowrap">Nombre / Razón Social</th>
                <th class="sort pe-1 align-middle white-space-nowrap">Teléfono</th>
                <th class="sort pe-1 align-middle white-space-nowrap">Email</th>
                <th class="sort pe-1 align-middle white-space-nowrap">Última Actividad</th>
                <th class="align-middle no-sort"></th>
              </tr>
            </thead>
            <tbody class="list">
              <tr v-for="cliente in clientes.data" :key="cliente.id" class="btn-reveal-trigger">
                <td class="align-middle white-space-nowrap">
                  <span class="badge bg-light text-dark border">{{ cliente.documento }}</span>
                </td>
                <td class="align-middle fw-semi-bold">{{ cliente.nombre }}</td>
                <td class="align-middle">{{ cliente.telefono || '-' }}</td>
                <td class="align-middle">{{ cliente.email || '-' }}</td>
                <td class="align-middle">{{ new Date(cliente.updated_at).toLocaleString() }}</td>
                <td class="align-middle white-space-nowrap text-end">
                  <button class="btn btn-link text-600 btn-sm" @click="openModal(cliente)" title="Editar">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="btn btn-link text-danger btn-sm" @click="deleteClient(cliente.id)" title="Eliminar">
                    <i class="fas fa-trash"></i>
                  </button>
                </td>
              </tr>
              <tr v-if="clientes.data.length === 0">
                <td colspan="6" class="text-center p-4">No se encontraron clientes.</td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <div class="d-flex justify-content-center mt-3" v-if="clientes.links && clientes.links.length > 3">
          <ul class="pagination pagination-sm mb-0">
            <li v-for="(link, k) in clientes.links" :key="k" class="page-item" :class="{ 'active': link.active, 'disabled': !link.url }">
              <Link class="page-link" :href="link.url || '#'" v-html="link.label" />
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Modal Cliente -->
    <div class="modal fade" id="clientModal" ref="clientModalRef" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ isEditing ? 'Editar Cliente' : 'Nuevo Cliente' }}</h5>
            <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form @submit.prevent="saveClient">
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label">DNI / RUC</label>
                <input type="text" v-model="clientForm.documento" class="form-control" :class="{ 'is-invalid': clientForm.errors.documento }" required>
                <div class="invalid-feedback">{{ clientForm.errors.documento }}</div>
              </div>
              <div class="mb-3">
                <label class="form-label">Nombre / Razón Social</label>
                <input type="text" v-model="clientForm.nombre" class="form-control" :class="{ 'is-invalid': clientForm.errors.nombre }" required>
                <div class="invalid-feedback">{{ clientForm.errors.nombre }}</div>
              </div>
              <div class="mb-3">
                <label class="form-label">Teléfono (Obligatorio p/ WhatsApp)</label>
                <input type="text" v-model="clientForm.telefono" class="form-control" :class="{ 'is-invalid': clientForm.errors.telefono }" required>
                <div class="invalid-feedback">{{ clientForm.errors.telefono }}</div>
              </div>
              <div class="mb-3">
                <label class="form-label">Email (Opcional)</label>
                <input type="email" v-model="clientForm.email" class="form-control" :class="{ 'is-invalid': clientForm.errors.email }">
                <div class="invalid-feedback">{{ clientForm.errors.email }}</div>
              </div>
            </div>
            <div class="modal-footer">
              <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
              <button class="btn btn-primary" type="submit" :disabled="clientForm.processing">
                Guardar
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
