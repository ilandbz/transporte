<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { ref, onMounted } from 'vue'
import { useForm, router } from '@inertiajs/vue3'

declare const bootstrap: any
declare const Swal: any

interface Tariff {
  id: number
  origen_tramo: string
  destino_tramo: string
  ubigeo_origen: string
  ubigeo_destino: string
  precio: string
  clase: string
}

interface Route {
  id: number
  nombre: string
  origen: string
  destino: string
  ubigeo_origen: string
  ubigeo_destino: string
  paradas: string[]
  activo: boolean
  tariffs_count: number
  tariffs: Tariff[]
}

interface Props {
  routes: Route[]
}

const props = defineProps<Props>()

const expandedRoute = ref<number | null>(null)

const routeModalRef = ref<HTMLElement | null>(null)
const tariffModalRef = ref<HTMLElement | null>(null)
let bsRouteModal: any = null
let bsTariffModal: any = null
const currentRouteId = ref<number | null>(null)
const editingTariffId = ref<number | null>(null)

const routeForm = useForm({
  nombre: '',
  origen: '',
  destino: '',
  ubigeo_origen: '',
  ubigeo_destino: '',
  paradas: [] as string[],
})

const tariffForm = useForm({
  origen_tramo: '',
  destino_tramo: '',
  ubigeo_origen: '',
  ubigeo_destino: '',
  precio: '',
  clase: 'normal',
})

onMounted(() => {
  if (routeModalRef.value) bsRouteModal = new bootstrap.Modal(routeModalRef.value)
  if (tariffModalRef.value) bsTariffModal = new bootstrap.Modal(tariffModalRef.value)
})

function toggleExpand(id: number) {
  expandedRoute.value = expandedRoute.value === id ? null : id
}

function toggleActivo(r: Route) {
  router.patch(`/settings/routes/${r.id}/toggle`)
}

function openRouteModal() {
  routeForm.reset()
  routeForm.clearErrors()
  bsRouteModal?.show()
}

function submitRoute() {
  routeForm.post('/settings/routes', {
    onSuccess: () => {
      bsRouteModal?.hide()
      routeForm.reset()
    },
  })
}

function openTariffModal(routeId: number, tariff?: Tariff) {
  currentRouteId.value = routeId
  tariffForm.clearErrors()
  
  if (tariff) {
    editingTariffId.value = tariff.id
    tariffForm.origen_tramo = tariff.origen_tramo
    tariffForm.destino_tramo = tariff.destino_tramo
    tariffForm.ubigeo_origen = tariff.ubigeo_origen || ''
    tariffForm.ubigeo_destino = tariff.ubigeo_destino || ''
    tariffForm.precio = tariff.precio
    tariffForm.clase = tariff.clase
  } else {
    editingTariffId.value = null
    tariffForm.reset()
  }
  
  bsTariffModal?.show()
}

function submitTariff() {
  if (editingTariffId.value) {
    tariffForm.put(`/settings/routes/tariffs/${editingTariffId.value}`, {
      onSuccess: () => {
        bsTariffModal?.hide()
        tariffForm.reset()
        editingTariffId.value = null
      },
    })
  } else {
    if (!currentRouteId.value) return
    tariffForm.post(`/settings/routes/${currentRouteId.value}/tariffs`, {
      onSuccess: () => {
        bsTariffModal?.hide()
        tariffForm.reset()
      },
    })
  }
}

async function deleteTariff(tariffId: number) {
  const result = await Swal.fire({
    title: '¿Eliminar tarifa?',
    text: 'Esta acción no se puede deshacer.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#e63757',
    cancelButtonColor: '#748194',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
  })
  if (result.isConfirmed) {
    router.delete(`/settings/routes/tariffs/${tariffId}`)
  }
}

function formatMoney(v: string) {
  return `S/ ${Number(v).toFixed(2)}`
}
</script>

<template>
  <AppLayout>
      
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
      <div>
        <h5 class="mb-0 fw-semibold">Rutas y tarifas</h5>
        <p class="text-muted mb-0 small">{{ routes.length }} rutas registradas</p>
      </div>
      <button class="btn btn-success" @click="openRouteModal">
        <i class="fas fa-plus me-1"></i> Nueva ruta
      </button>
    </div>

    <!-- Tabla de rutas -->
    <div class="card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th class="text-center" style="width: 50px;"></th>
                <th>Nombre</th>
                <th>Origen → Destino</th>
                <th>Paradas</th>
                <th class="text-center">Tarifas</th>
                <th class="text-center">Estado</th>
                <th class="text-center">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="r in routes" :key="r.id">
                <tr :class="[!r.activo && 'opacity-50']">
                  <td class="text-center">
                    <button class="btn btn-link text-secondary p-0 shadow-none" @click="toggleExpand(r.id)">
                      <i :class="expandedRoute === r.id ? 'fas fa-chevron-down' : 'fas fa-chevron-right'"></i>
                    </button>
                  </td>
                  <td class="fw-medium">{{ r.nombre }}</td>
                  <td>
                    {{ r.origen }} <i class="fas fa-arrow-right mx-1 text-muted"></i> {{ r.destino }}
                    <span class="d-block small text-muted">{{ r.ubigeo_origen }} / {{ r.ubigeo_destino }}</span>
                  </td>
                  <td class="small text-muted">
                    {{ r.paradas?.length ? r.paradas.join(', ') : '—' }}
                  </td>
                  <td class="text-center">
                    <span class="badge bg-info">
                      {{ r.tariffs_count }} tarifas
                    </span>
                  </td>
                  <td class="text-center">
                    <div class="form-check form-switch d-inline-block m-0">
                      <input class="form-check-input" type="checkbox" :checked="r.activo" @change="toggleActivo(r)">
                    </div>
                  </td>
                  <td class="text-center">
                    <button class="btn btn-sm btn-outline-success" @click="openTariffModal(r.id)">
                      <i class="fas fa-plus"></i> Tarifa
                    </button>
                  </td>
                </tr>

                <!-- Sub-tabla de tarifas -->
                <tr v-if="expandedRoute === r.id" class="table-light">
                  <td colspan="7" class="p-3">
                    <div class="card shadow-none border bg-white">
                      <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                          <thead class="table-light text-muted small">
                            <tr>
                              <th>Origen tramo</th>
                              <th>Destino tramo</th>
                              <th class="text-center">Precio</th>
                              <th class="text-center">Clase</th>
                              <th class="text-center">Acción</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr v-for="t in r.tariffs" :key="t.id">
                              <td>{{ t.origen_tramo }}</td>
                              <td>{{ t.destino_tramo }}</td>
                              <td class="text-center fw-bold text-success">{{ formatMoney(t.precio) }}</td>
                              <td class="text-center">
                                <span :class="['badge', t.clase === 'vip' ? 'bg-warning text-dark' : 'bg-secondary']">
                                  {{ t.clase }}
                                </span>
                              </td>
                              <td class="text-center">
                                <button class="btn btn-sm btn-link text-primary p-0 shadow-none me-3" @click="openTariffModal(r.id, t)" title="Editar">
                                  <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-link text-danger p-0 shadow-none" @click="deleteTariff(t.id)" title="Eliminar">
                                  <i class="fas fa-trash"></i>
                                </button>
                              </td>
                            </tr>
                            <tr v-if="r.tariffs.length === 0">
                              <td colspan="5" class="text-center text-muted py-3">Sin tarifas — agrega una con el botón "+ Tarifa"</td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal nueva ruta -->
    <div class="modal fade" ref="routeModalRef" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Nueva ruta</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Nombre</label>
              <input v-model="routeForm.nombre" type="text" class="form-control"
                :class="{ 'is-invalid': routeForm.errors.nombre }" placeholder="Huánuco - Puños" />
              <div v-if="routeForm.errors.nombre" class="invalid-feedback">{{ routeForm.errors.nombre }}</div>
            </div>
            <div class="row g-3 mb-3">
              <div class="col-6">
                <label class="form-label">Origen</label>
                <input v-model="routeForm.origen" type="text" class="form-control"
                  :class="{ 'is-invalid': routeForm.errors.origen }" placeholder="Huánuco" />
                <div v-if="routeForm.errors.origen" class="invalid-feedback">{{ routeForm.errors.origen }}</div>
              </div>
              <div class="col-6">
                <label class="form-label">Destino</label>
                <input v-model="routeForm.destino" type="text" class="form-control"
                  :class="{ 'is-invalid': routeForm.errors.destino }" placeholder="Puños" />
                <div v-if="routeForm.errors.destino" class="invalid-feedback">{{ routeForm.errors.destino }}</div>
              </div>
            </div>
            <div class="row g-3">
              <div class="col-6">
                <label class="form-label">Ubigeo origen</label>
                <input v-model="routeForm.ubigeo_origen" type="text" maxlength="6" class="form-control"
                  :class="{ 'is-invalid': routeForm.errors.ubigeo_origen }" placeholder="100101" />
                <div v-if="routeForm.errors.ubigeo_origen" class="invalid-feedback">{{ routeForm.errors.ubigeo_origen }}</div>
              </div>
              <div class="col-6">
                <label class="form-label">Ubigeo destino</label>
                <input v-model="routeForm.ubigeo_destino" type="text" maxlength="6" class="form-control"
                  :class="{ 'is-invalid': routeForm.errors.ubigeo_destino }" placeholder="100801" />
                <div v-if="routeForm.errors.ubigeo_destino" class="invalid-feedback">{{ routeForm.errors.ubigeo_destino }}</div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button class="btn btn-success" @click="submitRoute" :disabled="routeForm.processing">
              <span v-if="routeForm.processing"><i class="fas fa-spinner fa-spin me-1"></i> Guardando...</span>
              <span v-else><i class="fas fa-save me-1"></i> Crear ruta</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal nueva tarifa -->
    <div class="modal fade" ref="tariffModalRef" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ editingTariffId ? 'Editar tarifa' : 'Agregar tarifa' }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3 mb-3">
              <div class="col-6">
                <label class="form-label">Origen tramo</label>
                <input v-model="tariffForm.origen_tramo" type="text" class="form-control"
                  :class="{ 'is-invalid': tariffForm.errors.origen_tramo }" placeholder="Huánuco" />
                <div v-if="tariffForm.errors.origen_tramo" class="invalid-feedback">{{ tariffForm.errors.origen_tramo }}</div>
              </div>
              <div class="col-6">
                <label class="form-label">Destino tramo</label>
                <input v-model="tariffForm.destino_tramo" type="text" class="form-control"
                  :class="{ 'is-invalid': tariffForm.errors.destino_tramo }" placeholder="Llata" />
                <div v-if="tariffForm.errors.destino_tramo" class="invalid-feedback">{{ tariffForm.errors.destino_tramo }}</div>
              </div>
            </div>
            <div class="row g-3 mb-3">
              <div class="col-6">
                <label class="form-label">Ubigeo origen</label>
                <input v-model="tariffForm.ubigeo_origen" type="text" maxlength="6" class="form-control"
                  :class="{ 'is-invalid': tariffForm.errors.ubigeo_origen }" placeholder="100101" />
                <div v-if="tariffForm.errors.ubigeo_origen" class="invalid-feedback">{{ tariffForm.errors.ubigeo_origen }}</div>
              </div>
              <div class="col-6">
                <label class="form-label">Ubigeo destino</label>
                <input v-model="tariffForm.ubigeo_destino" type="text" maxlength="6" class="form-control"
                  :class="{ 'is-invalid': tariffForm.errors.ubigeo_destino }" placeholder="100301" />
                <div v-if="tariffForm.errors.ubigeo_destino" class="invalid-feedback">{{ tariffForm.errors.ubigeo_destino }}</div>
              </div>
            </div>
            <div class="row g-3">
              <div class="col-6">
                <label class="form-label">Precio (S/)</label>
                <input v-model="tariffForm.precio" type="number" min="0" step="0.50" class="form-control"
                  :class="{ 'is-invalid': tariffForm.errors.precio }" placeholder="15.00" />
                <div v-if="tariffForm.errors.precio" class="invalid-feedback">{{ tariffForm.errors.precio }}</div>
              </div>
              <div class="col-6">
                <label class="form-label">Clase</label>
                <select v-model="tariffForm.clase" class="form-select">
                  <option value="normal">Normal</option>
                  <option value="vip">VIP</option>
                </select>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button class="btn btn-success" @click="submitTariff" :disabled="tariffForm.processing">
              <span v-if="tariffForm.processing"><i class="fas fa-spinner fa-spin me-1"></i> Guardando...</span>
              <span v-else><i class="fas fa-save me-1"></i> Guardar tarifa</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    </AppLayout>
</template>
