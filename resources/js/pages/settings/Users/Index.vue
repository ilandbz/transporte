<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { ref, onMounted } from 'vue'
import { useForm, router } from '@inertiajs/vue3'

declare const bootstrap: any
declare const Swal: any

interface User {
  id: number
  name: string
  email: string
  role: string
  created_at: string
}

interface Props {
  users: User[]
}

const props = defineProps<Props>()

const modalRef = ref<HTMLElement | null>(null)
const modalTitle = ref('Nuevo usuario')
let bsModal: any = null
const editingId = ref<number | null>(null)

const form = useForm({
  name: '',
  email: '',
  role: 'conductor',
  password: '',
})

onMounted(() => {
  if (modalRef.value) {
    bsModal = new bootstrap.Modal(modalRef.value)
  }
})

function openCreate() {
  editingId.value = null
  modalTitle.value = 'Nuevo usuario'
  form.reset()
  form.clearErrors()
  bsModal?.show()
}

function openEdit(u: User) {
  editingId.value = u.id
  modalTitle.value = 'Editar usuario'
  form.name = u.name
  form.email = u.email
  form.role = u.role
  form.password = ''
  form.clearErrors()
  bsModal?.show()
}

function submit() {
  if (editingId.value) {
    form.put(`/settings/users/${editingId.value}`, {
      onSuccess: () => bsModal?.hide(),
    })
  } else {
    form.post('/settings/users', {
      onSuccess: () => bsModal?.hide(),
    })
  }
}

async function deleteUser(u: User) {
  const result = await Swal.fire({
    title: '¿Eliminar usuario?',
    text: `Se eliminará a ${u.name}. Esta acción no se puede deshacer.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#e63757',
    cancelButtonColor: '#748194',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
  })
  if (result.isConfirmed) {
    router.delete(`/settings/users/${u.id}`)
  }
}

const roleBadge: Record<string, string> = {
  admin:     'bg-danger',
  conductor: 'bg-primary',
  counter:   'bg-success',
}

function formatDate(d: string) {
  return new Date(d).toLocaleDateString('es-PE')
}
</script>

<template>
  <AppLayout>
  
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
      <div>
        <h5 class="mb-0 fw-semibold">Usuarios del sistema</h5>
        <p class="text-muted mb-0 small">{{ users.length }} usuarios registrados</p>
      </div>
      <button class="btn btn-success" @click="openCreate">
        <i class="fas fa-plus me-1"></i> Nuevo usuario
      </button>
    </div>

    <!-- Tabla -->
    <div class="card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Nombre</th>
                <th>Email</th>
                <th>Rol</th>
                <th>Registro</th>
                <th class="text-center">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="u in users" :key="u.id">
                <td class="fw-semibold">{{ u.name }}</td>
                <td class="text-muted">{{ u.email }}</td>
                <td>
                  <span :class="['badge', roleBadge[u.role] ?? 'bg-secondary']">
                    {{ u.role }}
                  </span>
                </td>
                <td class="text-muted small">{{ formatDate(u.created_at) }}</td>
                <td class="text-center">
                  <button class="btn btn-sm btn-outline-primary me-1" @click="openEdit(u)">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="btn btn-sm btn-outline-danger" @click="deleteUser(u)">
                    <i class="fas fa-trash"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Bootstrap Modal -->
    <div class="modal fade" ref="modalRef" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ modalTitle }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Nombre completo</label>
              <input v-model="form.name" type="text" class="form-control"
                :class="{ 'is-invalid': form.errors.name }" placeholder="Pedro Ramos Quispe" />
              <div v-if="form.errors.name" class="invalid-feedback">{{ form.errors.name }}</div>
            </div>
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input v-model="form.email" type="email" class="form-control"
                :class="{ 'is-invalid': form.errors.email }" placeholder="pedro@shinhua.pe" />
              <div v-if="form.errors.email" class="invalid-feedback">{{ form.errors.email }}</div>
            </div>
            <div class="mb-3">
              <label class="form-label">Rol</label>
              <select v-model="form.role" class="form-select">
                <option value="admin">Administrador</option>
                <option value="conductor">Conductor</option>
                <option value="counter">Counter</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">
                Contraseña
                <span v-if="editingId" class="text-muted small">(dejar vacío para no cambiar)</span>
              </label>
              <input v-model="form.password" type="password" class="form-control"
                :class="{ 'is-invalid': form.errors.password }" placeholder="Mínimo 8 caracteres" />
              <div v-if="form.errors.password" class="invalid-feedback">{{ form.errors.password }}</div>
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
                {{ editingId ? 'Actualizar' : 'Crear usuario' }}
              </span>
            </button>
          </div>
        </div>
      </div>
    </div>

    </AppLayout>
</template>
