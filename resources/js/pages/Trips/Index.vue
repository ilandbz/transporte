<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { ref, onMounted } from 'vue'

declare const flatpickr: any
declare const bootstrap: any
declare const Swal: any

interface RouteModel {
  id: number
  nombre: string
  origen: string
  destino: string
}

interface VehicleModel {
  id: number
  placa: string
  capacidad_asientos: number
}

interface Trip {
  id: number
  placa_vehiculo: string
  numero_manifiesto: string | null
  fecha_salida: string
  estado: 'abierto' | 'en_ruta' | 'cerrado'
  route: { nombre: string; origen: string; destino: string }
  conductor: { name: string }
  tickets_count: number
  asientos_ocupados: number[]
  vehicle: { capacidad_asientos: number }
}

interface Props {
  trips: Trip[]
  fecha: string
  routes: RouteModel[]
  vehicles: VehicleModel[]
}

const props = defineProps<Props>()
const dateRef = ref<HTMLInputElement | null>(null)

let tripModal: any = null
let ticketModal: any = null

onMounted(() => {
  if (dateRef.value) {
    flatpickr(dateRef.value, {
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
  
  tripModal = new bootstrap.Modal(document.getElementById('tripModal'))
  ticketModal = new bootstrap.Modal(document.getElementById('ticketModal'))
})

const estadoBadge: Record<string, string> = {
  abierto: 'bg-primary',
  en_ruta: 'bg-success',
  cerrado: 'bg-secondary',
}

function formatTime(d: string) {
  const date = new Date(d);
  let hours = date.getHours();
  const minutes = date.getMinutes().toString().padStart(2, '0');
  const ampm = hours >= 12 ? 'p. m.' : 'a. m.';
  hours = hours % 12;
  hours = hours ? hours : 12;
  return `${hours.toString().padStart(2, '0')}:${minutes} ${ampm}`;
}

// ========================
// NUEVO VIAJE
// ========================
const tripForm = useForm({
  route_id: '',
  vehicle_id: '',
  fecha_salida: '',
  numero_manifiesto: ''
})

function openTripModal() {
  tripForm.reset()
  tripForm.clearErrors()
  tripModal.show()
}

function submitTrip() {
  tripForm.post('/trips', {
    onSuccess: () => {
      tripModal.hide()
      Swal.fire('¡Éxito!', 'Viaje registrado correctamente', 'success')
    },
    onError: (errors) => {
        if (Object.keys(errors).length > 0) {
            Swal.fire('Error', 'Por favor verifica los datos ingresados.', 'error');
        }
    }
  })
}

function deleteTrip(trip: Trip) {
  Swal.fire({
    title: '¿Eliminar Viaje?',
    text: "Esta acción no se puede deshacer.",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  }).then((result: any) => {
    if (result.isConfirmed) {
      router.delete(`/trips/${trip.id}`, {
        onSuccess: () => {
          Swal.fire('¡Eliminado!', 'El viaje ha sido eliminado.', 'success')
        },
        onError: (errors: any) => {
            let msg = 'No se pudo eliminar el viaje.';
            if (errors.error) {
                msg = errors.error;
            }
            Swal.fire('Error', msg, 'error');
        }
      })
    }
  })
}

// ========================
// VENDER PASAJE
// ========================

function generateUUID() {
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
    var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
    return v.toString(16);
  });
}

const activeTrip = ref<Trip | null>(null)

const ticketForm = useForm({
  trip_id: null as number | null,
  uuid_local: '',
  numero_asiento: null as number | null,
  clase: 'normal',
  origen_tramo: '',
  destino_tramo: '',
  ubigeo_origen: '100101', // mock por defecto
  ubigeo_destino: '100101', // mock por defecto
  dni_pasajero: '',
  nombre_pasajero: '',
  metodo_pago: 'efectivo',
  tipo_documento: 'BOLETA',
  emitido_en: '',
  emitido_en_contingencia: false,
})

function openTicketModal(trip: Trip) {
  activeTrip.value = trip
  ticketForm.reset()
  ticketForm.clearErrors()
  ticketForm.trip_id = trip.id
  ticketForm.uuid_local = generateUUID()
  
  if (trip.route) {
    ticketForm.origen_tramo = trip.route.origen
    ticketForm.destino_tramo = trip.route.destino
  }
  
  const now = new Date()
  now.setMinutes(now.getMinutes() - now.getTimezoneOffset())
  ticketForm.emitido_en = now.toISOString().slice(0, 16)
  
  ticketModal.show()
}

function submitTicket() {
  ticketForm.post('/tickets', {
    onSuccess: () => {
      ticketModal.hide()
      Swal.fire('¡Éxito!', 'Pasaje vendido correctamente', 'success')
    },
    onError: (errors) => {
        let msg = 'Verifica los datos ingresados.';
        if (errors.error) {
            msg = errors.error;
        } else if (Object.values(errors).length > 0) {
            msg = Object.values(errors)[0] as string;
        }
        Swal.fire('Error', msg, 'error');
    }
  })
}
</script>

<template>
  <AppLayout>
      <Head title="Viajes" />

    <!-- Selector fecha -->
    <div class="card mb-4">
      <div class="card-body py-3 d-flex align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3">
          <label class="form-label mb-0 fw-medium">Fecha:</label>
          <div style="max-width: 200px;">
            <input type="text" ref="dateRef" class="form-control" placeholder="Seleccionar fecha" />
          </div>
          <span class="text-muted small ms-2">{{ trips.length }} viaje(s) encontrados</span>
        </div>
        <div>
          <button @click="openTripModal" class="btn btn-primary btn-sm">
            <i class="fas fa-plus me-1"></i> Nuevo Viaje
          </button>
        </div>
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
                <th class="text-end">Acciones</th>
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
                <td class="text-end text-nowrap">
                  <button v-if="t.estado === 'abierto'" @click="openTicketModal(t)" class="btn btn-success btn-sm me-1" title="Vender Pasaje">
                    <i class="fas fa-ticket-alt"></i>
                  </button>
                  <button @click="deleteTrip(t)" class="btn btn-danger btn-sm" title="Eliminar Viaje">
                    <i class="fas fa-trash"></i>
                  </button>
                </td>
              </tr>
              <tr v-if="trips.length === 0">
                <td colspan="8" class="text-center text-muted py-5">
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

  <!-- Modales como hermanos de AppLayout para evitar estilos anidados -->
  <!-- Modal Nuevo Viaje -->
  <div class="modal fade" id="tripModal" tabindex="-1" aria-hidden="true" ref="tripModalRef">
    <div class="modal-dialog">
      <div class="modal-content">
        <form @submit.prevent="submitTrip">
          <div class="modal-header">
            <h5 class="modal-title">Registrar Nuevo Viaje</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Ruta</label>
              <select v-model="tripForm.route_id" class="form-select" :class="{ 'is-invalid': tripForm.errors.route_id }" required>
                <option value="" disabled>Seleccionar ruta...</option>
                <option v-for="r in routes" :key="r.id" :value="r.id">{{ r.nombre }}</option>
              </select>
              <div class="invalid-feedback">{{ tripForm.errors.route_id }}</div>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Vehículo</label>
              <select v-model="tripForm.vehicle_id" class="form-select" :class="{ 'is-invalid': tripForm.errors.vehicle_id }" required>
                <option value="" disabled>Seleccionar vehículo...</option>
                <option v-for="v in vehicles" :key="v.id" :value="v.id">{{ v.placa }} ({{ v.capacidad_asientos }} as.)</option>
              </select>
              <div class="invalid-feedback">{{ tripForm.errors.vehicle_id }}</div>
            </div>

            <div class="mb-3">
              <label class="form-label">Fecha y Hora de Salida</label>
              <input type="datetime-local" v-model="tripForm.fecha_salida" class="form-control" :class="{ 'is-invalid': tripForm.errors.fecha_salida }" required>
              <div class="invalid-feedback">{{ tripForm.errors.fecha_salida }}</div>
            </div>

            <div class="mb-3">
              <label class="form-label">Manifiesto (Opcional)</label>
              <input type="text" v-model="tripForm.numero_manifiesto" class="form-control" :class="{ 'is-invalid': tripForm.errors.numero_manifiesto }">
              <div class="invalid-feedback">{{ tripForm.errors.numero_manifiesto }}</div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary" :disabled="tripForm.processing">Guardar</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal Vender Pasaje -->
  <div class="modal fade" id="ticketModal" tabindex="-1" aria-hidden="true" ref="ticketModalRef">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form @submit.prevent="submitTicket">
          <div class="modal-header">
            <h5 class="modal-title">Vender Pasaje</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">DNI Pasajero (Opcional)</label>
                <input type="text" v-model="ticketForm.dni_pasajero" class="form-control" :class="{ 'is-invalid': ticketForm.errors.dni_pasajero }" maxlength="15">
                <div class="invalid-feedback">{{ ticketForm.errors.dni_pasajero }}</div>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Nombre Pasajero (Opcional)</label>
                <input type="text" v-model="ticketForm.nombre_pasajero" class="form-control" :class="{ 'is-invalid': ticketForm.errors.nombre_pasajero }">
                <div class="invalid-feedback">{{ ticketForm.errors.nombre_pasajero }}</div>
              </div>
            </div>
            
            <!-- Grid de Asientos -->
            <div class="mb-4 bg-light p-3 rounded" v-if="activeTrip">
              <label class="form-label d-block text-center fw-bold mb-3"><i class="fas fa-bus"></i> Seleccionar Asiento</label>
              
              <div class="d-flex justify-content-center mb-3 gap-3">
                <span class="badge bg-secondary"><i class="fas fa-couch"></i> Ocupado</span>
                <span class="badge bg-success"><i class="fas fa-couch"></i> Seleccionado</span>
                <span class="badge bg-white text-dark border"><i class="fas fa-couch"></i> Libre</span>
              </div>

              <div class="d-flex flex-wrap gap-2 justify-content-center mx-auto" style="max-width: 350px;">
                <template v-for="n in activeTrip.vehicle.capacidad_asientos" :key="n">
                  <button type="button" class="btn btn-sm d-flex align-items-center justify-content-center shadow-none"
                          :class="{
                            'btn-secondary': activeTrip.asientos_ocupados.includes(n),
                            'btn-success': ticketForm.numero_asiento === n,
                            'btn-outline-secondary bg-white': !activeTrip.asientos_ocupados.includes(n) && ticketForm.numero_asiento !== n
                          }"
                          :disabled="activeTrip.asientos_ocupados.includes(n)"
                          @click="ticketForm.numero_asiento = n"
                          style="width: 45px; height: 45px; font-weight: bold; font-size: 1.1rem;">
                    {{ n }}
                  </button>
                </template>
              </div>
              <div v-if="ticketForm.errors.numero_asiento" class="text-danger text-center small mt-2 fw-medium">
                {{ ticketForm.errors.numero_asiento }}
              </div>
            </div>

            <div class="row">
              <div class="col-md-4 mb-3">
                <label class="form-label">Clase</label>
                <select v-model="ticketForm.clase" class="form-select" :class="{ 'is-invalid': ticketForm.errors.clase }" required>
                  <option value="normal">Normal</option>
                  <option value="vip">VIP</option>
                </select>
                <div class="invalid-feedback">{{ ticketForm.errors.clase }}</div>
              </div>
              <div class="col-md-4 mb-3">
                <label class="form-label">Origen Tramo</label>
                <input type="text" v-model="ticketForm.origen_tramo" class="form-control" :class="{ 'is-invalid': ticketForm.errors.origen_tramo }" required>
                <div class="invalid-feedback">{{ ticketForm.errors.origen_tramo }}</div>
              </div>
              <div class="col-md-4 mb-3">
                <label class="form-label">Destino Tramo</label>
                <input type="text" v-model="ticketForm.destino_tramo" class="form-control" :class="{ 'is-invalid': ticketForm.errors.destino_tramo }" required>
                <div class="invalid-feedback">{{ ticketForm.errors.destino_tramo }}</div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-4 mb-3">
                <label class="form-label">Tipo Documento</label>
                <select v-model="ticketForm.tipo_documento" class="form-select" :class="{ 'is-invalid': ticketForm.errors.tipo_documento }" required>
                  <option value="BOLETA">Boleta</option>
                  <option value="FACTURA">Factura</option>
                  <option value="TICKET_INTERNO">Ticket Interno</option>
                </select>
                <div class="invalid-feedback">{{ ticketForm.errors.tipo_documento }}</div>
              </div>
              <div class="col-md-4 mb-3">
                <label class="form-label">Método Pago</label>
                <select v-model="ticketForm.metodo_pago" class="form-select" :class="{ 'is-invalid': ticketForm.errors.metodo_pago }" required>
                  <option value="efectivo">Efectivo</option>
                  <option value="yape">Yape</option>
                  <option value="plin">Plin</option>
                  <option value="transferencia">Transferencia</option>
                </select>
                <div class="invalid-feedback">{{ ticketForm.errors.metodo_pago }}</div>
              </div>
              <div class="col-md-4 mb-3">
                <label class="form-label">Fecha Emisión</label>
                <input type="datetime-local" v-model="ticketForm.emitido_en" class="form-control" :class="{ 'is-invalid': ticketForm.errors.emitido_en }" required>
                <div class="invalid-feedback">{{ ticketForm.errors.emitido_en }}</div>
              </div>
            </div>
            
            <div class="form-check mb-3">
              <input class="form-check-input" type="checkbox" v-model="ticketForm.emitido_en_contingencia" id="contingencia">
              <label class="form-check-label" for="contingencia">
                Emitido en contingencia (sin conexión a SUNAT)
              </label>
            </div>

          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-success" :disabled="ticketForm.processing">Vender Pasaje</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
