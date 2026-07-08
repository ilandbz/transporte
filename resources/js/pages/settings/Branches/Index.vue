<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { ref, onMounted } from 'vue'
import { useForm, router } from '@inertiajs/vue3'

declare const bootstrap: any

interface Branch {
  id: number
  name: string
  address: string | null
  is_active: boolean
  users_count?: number
  tickets_count?: number
  packages_count?: number
}

interface Props {
  branches: Branch[]
}

const props = defineProps<Props>()

const modalRef = ref<HTMLElement | null>(null)
let bsModal: any = null
const editingBranch = ref<Branch | null>(null)

const form = useForm({
  name: '',
  address: '',
  is_active: true,
})

onMounted(() => {
  if (modalRef.value) {
    bsModal = new bootstrap.Modal(modalRef.value)
  }
})

const openModal = (branch: Branch | null = null) => {
  form.clearErrors()
  editingBranch.value = branch
  
  if (branch) {
    form.name = branch.name
    form.address = branch.address || ''
    form.is_active = branch.is_active
  } else {
    form.reset()
    form.is_active = true
  }
  
  bsModal?.show()
}

const closeModal = () => {
  bsModal?.hide()
  setTimeout(() => {
    form.reset()
    editingBranch.value = null
  }, 300)
}

const submit = () => {
  if (editingBranch.value) {
    form.put(route('settings.branches.update', editingBranch.value.id), {
      onSuccess: () => closeModal()
    })
  } else {
    form.post(route('settings.branches.store'), {
      onSuccess: () => closeModal()
    })
  }
}

const toggleStatus = (branch: Branch) => {
  router.patch(route('settings.branches.toggle', branch.id))
}
</script>

<template>
  <AppLayout title="Sucursales">
    <div class="card mb-3">
      <div class="card-header border-bottom">
        <div class="row flex-between-end">
          <div class="col-auto align-self-center">
            <h5 class="mb-0" data-anchor="data-anchor">Sucursales / Agencias</h5>
          </div>
          <div class="col-auto ms-auto">
            <button class="btn btn-primary btn-sm" @click="openModal()">
              <i class="fas fa-plus me-1"></i>Nueva Sucursal
            </button>
          </div>
        </div>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive scrollbar">
          <table class="table table-hover table-striped overflow-hidden">
            <thead class="bg-200 text-900">
              <tr>
                <th class="sort pe-1 align-middle white-space-nowrap">Nombre</th>
                <th class="sort pe-1 align-middle white-space-nowrap">Dirección</th>
                <th class="sort pe-1 align-middle text-center">Usuarios</th>
                <th class="sort pe-1 align-middle text-center">Tickets</th>
                <th class="sort pe-1 align-middle text-center">Encomiendas</th>
                <th class="sort pe-1 align-middle text-center">Estado</th>
                <th class="align-middle no-sort text-end">Acciones</th>
              </tr>
            </thead>
            <tbody class="list">
              <tr v-if="branches.length === 0">
                <td colspan="7" class="text-center p-4">No hay sucursales registradas.</td>
              </tr>
              <tr v-for="branch in branches" :key="branch.id" class="align-middle">
                <td class="text-nowrap fw-semi-bold">{{ branch.name }}</td>
                <td class="text-nowrap">{{ branch.address || '-' }}</td>
                <td class="text-center">{{ branch.users_count || 0 }}</td>
                <td class="text-center">{{ branch.tickets_count || 0 }}</td>
                <td class="text-center">{{ branch.packages_count || 0 }}</td>
                <td class="text-center">
                  <span class="badge rounded-pill" :class="branch.is_active ? 'badge-subtle-success' : 'badge-subtle-secondary'">
                    {{ branch.is_active ? 'Activa' : 'Inactiva' }}
                  </span>
                </td>
                <td class="text-end text-nowrap">
                  <button class="btn btn-sm btn-falcon-default me-2" @click="openModal(branch)" title="Editar">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="btn btn-sm" 
                          :class="branch.is_active ? 'btn-falcon-danger' : 'btn-falcon-success'"
                          @click="toggleStatus(branch)"
                          :title="branch.is_active ? 'Desactivar' : 'Activar'">
                    <i class="fas" :class="branch.is_active ? 'fa-power-off' : 'fa-check'"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal Form -->
    <div class="modal fade" id="branchModal" tabindex="-1" aria-hidden="true" ref="modalRef">
      <div class="modal-dialog">
        <div class="modal-content">
          <form @submit.prevent="submit">
            <div class="modal-header">
              <h5 class="modal-title">{{ editingBranch ? 'Editar Sucursal' : 'Nueva Sucursal' }}</h5>
              <button class="btn-close" type="button" @click="closeModal"></button>
            </div>
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label" for="name">Nombre / Denominación <span class="text-danger">*</span></label>
                <input class="form-control" id="name" type="text" v-model="form.name" :class="{ 'is-invalid': form.errors.name }" required>
                <div class="invalid-feedback" v-if="form.errors.name">{{ form.errors.name }}</div>
              </div>
              
              <div class="mb-3">
                <label class="form-label" for="address">Dirección</label>
                <input class="form-control" id="address" type="text" v-model="form.address" :class="{ 'is-invalid': form.errors.address }">
                <div class="invalid-feedback" v-if="form.errors.address">{{ form.errors.address }}</div>
              </div>

              <div class="form-check form-switch mt-3">
                <input class="form-check-input" id="is_active" type="checkbox" v-model="form.is_active">
                <label class="form-check-label" for="is_active">Sucursal Activa</label>
              </div>
            </div>
            <div class="modal-footer">
              <button class="btn btn-secondary" type="button" @click="closeModal">Cancelar</button>
              <button class="btn btn-primary" type="submit" :disabled="form.processing">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" v-if="form.processing"></span>
                {{ editingBranch ? 'Guardar Cambios' : 'Registrar' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
