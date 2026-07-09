<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { ref, onMounted, watch, computed } from 'vue'

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
  vehicle: { 
    capacidad_asientos: number
    layout_asientos?: {
      filas: number
      columnas: number
      pasillo: number | null
      asientos: { numero: number; clase: string }[]
    }
  }
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

import PackageFormModal from '@/components/PackageFormModal.vue'
const packageModal = ref<InstanceType<typeof PackageFormModal> | null>(null)

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
  id: null as number | null,
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
  telefono_pasajero: '',
  estado_pago: 'pagado',
  metodo_pago: 'efectivo',
  tipo_documento: 'BOLETA',
  facturar_tercero: false,
  documento_facturacion: '',
  nombre_facturacion: '',
  emitido_en: '',
  emitido_en_contingencia: false,
  estado: 'confirmado',
})

const isPending = computed(() => ticketForm.estado_pago === 'pendiente')

const isFormValid = computed(() => {
  if (ticketForm.processing) return false;
  
  if (ticketForm.estado_pago === 'pagado') {
    if (ticketForm.tipo_documento === 'FACTURA') {
      const ruc = ticketForm.documento_facturacion || '';
      const razonSocial = ticketForm.nombre_facturacion || '';
      if (ruc.length !== 11 || !/^\d+$/.test(ruc) || razonSocial.trim() === '') {
        return false;
      }
    } else if (ticketForm.tipo_documento === 'BOLETA' && ticketForm.facturar_tercero) {
      const dni = ticketForm.documento_facturacion || '';
      const nombre = ticketForm.nombre_facturacion || '';
      if ((dni.length !== 8 && dni.length !== 11) || nombre.trim() === '') {
        return false;
      }
    }
    if (!ticketForm.metodo_pago || ticketForm.metodo_pago === 'pendiente') {
      return false;
    }
  }
  return true;
})

const submitBtnText = computed(() => {
  return isPending.value ? 'Confirmar Reserva' : 'Vender Pasaje / Emitir CPE'
})

const submitBtnClass = computed(() => {
  return isPending.value ? 'btn-warning text-dark' : 'btn-success'
})

watch(() => ticketForm.estado_pago, (newVal) => {
  if (newVal === 'pendiente') {
    ticketForm.tipo_documento = 'TICKET_INTERNO'
    ticketForm.metodo_pago = 'pendiente'
  } else if (newVal === 'pagado') {
    if (ticketForm.tipo_documento === 'TICKET_INTERNO') {
      ticketForm.tipo_documento = 'BOLETA'
    }
    if (ticketForm.metodo_pago === 'pendiente') {
      ticketForm.metodo_pago = 'efectivo'
    }
  }
})

watch(() => ticketForm.tipo_documento, (newVal) => {
  if (newVal === 'BOLETA' || newVal === 'FACTURA') {
    ticketForm.estado_pago = 'pagado'
  }
})

function executeDynamicSubmit() {
  if (ticketForm.estado_pago === 'pendiente') {
    submitReservation()
  } else {
    submitTicket()
  }
}

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

function getTicketForSeat(n: number) {
  return activeTrip.value?.tickets?.find(t => t.numero_asiento === n)
}

const activeLayout = computed(() => {
  if (!activeTrip.value?.vehicle?.layout_asientos) return null;
  const layout = activeTrip.value.vehicle.layout_asientos;
  if (typeof layout === 'string') {
    try {
      return JSON.parse(layout);
    } catch (e) {
      return null;
    }
  }
  return layout;
});

function handleSeatClick(n: number) {
  const ticket = getTicketForSeat(n)
  
  if (!ticket) {
    // Asiento libre
    if (ticketForm.id) {
      // Estábamos editando una reserva, limpiar datos
      const oldTripId = ticketForm.trip_id;
      ticketForm.reset();
      ticketForm.clearErrors();
      ticketForm.trip_id = oldTripId;
      ticketForm.uuid_local = generateUUID();
      const now = new Date();
      now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
      ticketForm.emitido_en = now.toISOString().slice(0, 16);
    }
    ticketForm.numero_asiento = n
    ticketForm.id = null
  } else if (ticket.estado === 'reservado') {
    // Asiento reservado, cargar datos para confirmar o anular
    ticketForm.id = ticket.id
    ticketForm.numero_asiento = n
    ticketForm.dni_pasajero = ticket.dni_pasajero || ''
    ticketForm.nombre_pasajero = ticket.nombre_pasajero || ''
    ticketForm.telefono_pasajero = ticket.telefono_pasajero || ''
    ticketForm.clase = ticket.clase || 'normal'
    ticketForm.origen_tramo = ticket.origen_tramo
    ticketForm.destino_tramo = ticket.destino_tramo
    ticketForm.estado_pago = 'pagado'
    ticketForm.metodo_pago = 'efectivo'
    ticketForm.tipo_documento = 'BOLETA'
    ticketForm.documento_facturacion = ''
    ticketForm.nombre_facturacion = ''
    ticketForm.facturar_tercero = false
  } else {
    // Asiento confirmado, no hacer nada o mostrar alerta
    Swal.fire({
      icon: 'info',
      title: 'Asiento Ocupado',
      text: 'Este asiento ya está pagado o confirmado.',
      timer: 2000,
      showConfirmButton: false
    })
  }
}

async function buscarClientePasaje() {
  const dni = ticketForm.dni_pasajero
  if (!dni || (dni.length !== 8 && dni.length !== 11)) return
  
  try {
    const res = await fetch(`/clientes/search/${dni}`)
    if (res.ok) {
      const data = await res.json()
      if (data.encontrado) {
        ticketForm.nombre_pasajero = data.datos.nombre || ''
        ticketForm.telefono_pasajero = data.datos.telefono || ''
      }
    }
  } catch (error) {
    // console.log("Not found")
  }
}

async function buscarClienteFacturacion() {
  const doc = ticketForm.documento_facturacion
  if (!doc) return
  
  try {
    const res = await fetch(`/clientes/search/${doc}`)
    if (res.ok) {
      const data = await res.json()
      if (data.encontrado) {
        ticketForm.nombre_facturacion = data.datos.nombre || ''
      }
    }
  } catch (error) {
    // console.log("Not found")
  }
}

function submitTicket() {
  const url = ticketForm.id ? `/tickets/${ticketForm.id}/confirm-reservation` : '/tickets'
  const method = ticketForm.id ? 'put' : 'post'
  
  ticketForm.estado = 'confirmado'
  
  ticketForm[method](url, {
    onSuccess: () => {
      ticketModal.hide()
      Swal.fire('¡Éxito!', 'Pasaje vendido/confirmado correctamente', 'success')
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

function submitReservation() {
  ticketForm.estado = 'reservado'
  ticketForm.tipo_documento = 'TICKET_INTERNO'
  ticketForm.estado_pago = 'pendiente'
  
  ticketForm.post('/tickets', {
    onSuccess: () => {
      ticketModal.hide()
      Swal.fire('¡Reserva Exitosa!', 'El asiento ha sido reservado', 'success')
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

function anularReserva() {
  if (!ticketForm.id) return
  
  Swal.fire({
    title: '¿Anular reserva?',
    text: "Esta acción liberará el asiento.",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Sí, anular',
    cancelButtonText: 'No'
  }).then((result: any) => {
    if (result.isConfirmed) {
      router.delete(`/tickets/${ticketForm.id}`, {
        onSuccess: () => {
          ticketModal.hide()
          Swal.fire('Anulada', 'La reserva fue anulada.', 'success')
        }
      })
    }
  })
}

function togglePayment(ticket: any) {
  router.patch(`/tickets/${ticket.id}/toggle-payment`, {}, {
    preserveScroll: true,
    onSuccess: () => {
      // Toast or simple notification could be added here
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
                <th>Tipo</th>
                <th>Ruta</th>
                <th>Conductor</th>
                <th>Salida</th>
                <th class="text-center">Estado</th>
                <th class="text-center">Tickets</th>
                <th class="text-center">Asientos</th>
                <th class="text-end">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="t in trips" :key="t.id">
                <td class="font-monospace small text-muted">{{ t.numero_manifiesto ?? '—' }}</td>
                <td class="font-monospace fw-bold">{{ t.placa_vehiculo }}</td>
                <td>{{ t.vehicle?.tipo }}</td>
                <td>{{ t.route.nombre }}</td>
                <td>{{ t.conductor.name }}</td>
                <td>{{ formatTime(t.fecha_salida) }}</td>
                <td class="text-center">
                  <span :class="['badge', estadoBadge[t.estado]]">
                    {{ t.estado }}
                  </span>
                </td>
                <td class="text-center fw-medium">{{ t.tickets_count }}</td>
                <td class="text-center fw-medium">
                    {{ t.vehicle.asientos_ocupados?.length || 0 }} / {{ t.vehicle.capacidad_asientos }}
                </td>
                <td class="text-end text-nowrap">
                  <button v-if="t.estado === 'abierto'" @click="openTicketModal(t)" class="btn btn-success btn-sm me-1" title="Vender Pasaje">
                    <i class="fas fa-ticket-alt"></i>
                  </button>
                  <button v-if="t.estado === 'abierto'" @click="packageModal?.show(t.id)" class="btn btn-warning btn-sm me-1 text-white" title="Registrar Encomienda">
                    <i class="fas fa-box"></i>
                  </button>
                  <button @click="deleteTrip(t)" class="btn btn-danger btn-sm" title="Eliminar Viaje">
                    <i class="fas fa-trash"></i>
                  </button>
                </td>
              </tr>
              <tr v-if="trips.length === 0">
                <td colspan="10" class="text-center text-muted py-5">
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
              <div class="col-md-4 mb-3">
                <label class="form-label">DNI Pasajero</label>
                <input type="text" v-model="ticketForm.dni_pasajero" @blur="buscarClientePasaje" class="form-control" :class="{ 'is-invalid': ticketForm.errors.dni_pasajero }" maxlength="15">
                <div class="invalid-feedback">{{ ticketForm.errors.dni_pasajero }}</div>
              </div>
              <div class="col-md-4 mb-3">
                <label class="form-label">Nombre Pasajero</label>
                <input type="text" v-model="ticketForm.nombre_pasajero" class="form-control" :class="{ 'is-invalid': ticketForm.errors.nombre_pasajero }">
                <div class="invalid-feedback">{{ ticketForm.errors.nombre_pasajero }}</div>
              </div>
              <div class="col-md-4 mb-3">
                <label class="form-label">Celular (Para WhatsApp)</label>
                <input type="text" v-model="ticketForm.telefono_pasajero" class="form-control" :class="{ 'is-invalid': ticketForm.errors.telefono_pasajero }" required>
                <div class="invalid-feedback">{{ ticketForm.errors.telefono_pasajero }}</div>
              </div>
            </div>
            
              <!-- Grid de Asientos -->
            <div class="mb-4 bg-light p-3 rounded" v-if="activeTrip">
              <label class="form-label d-block text-center fw-bold mb-3"><i class="fas fa-bus"></i> Seleccionar Asiento</label>
              
              <div class="d-flex justify-content-center mb-4 gap-3">
                <span class="badge bg-secondary"><i class="fas fa-couch"></i> Confirmado</span>
                <span class="badge bg-warning text-dark"><i class="fas fa-couch"></i> Reservado</span>
                <span class="badge bg-success"><i class="fas fa-couch"></i> Seleccionado</span>
                <span class="badge bg-white text-dark border"><i class="fas fa-couch"></i> Libre</span>
              </div>

              <!-- Realistic Seat Layout -->
              <div v-if="activeLayout" class="d-flex justify-content-center">
                <div class="seat-container border rounded bg-white p-4 shadow-sm" style="max-width: 500px; position: relative;">
                  
                  <!-- Steering Wheel Area (Front) -->
                  <div class="d-flex justify-content-end mb-4 border-bottom pb-2">
                    <div class="text-center text-muted fw-bold small">
                      FRENTE <i class="fas fa-steering-wheel ms-1"></i>
                    </div>
                  </div>

                  <!-- Seats Grid -->
                  <div class="d-grid gap-3" 
                       :style="{
                         'grid-template-columns': `repeat(${activeLayout.columnas + (activeLayout.pasillo ? 1 : 0)}, auto)`,
                         'justify-content': 'center'
                       }">
                    
                    <template v-for="(seat, index) in activeLayout.asientos" :key="index">
                      
                      <!-- Add aisle space if needed before this seat -->
                      <div v-if="activeLayout.pasillo && (index % activeLayout.columnas) === activeLayout.pasillo" 
                           class="d-flex align-items-center justify-content-center" 
                           style="width: 30px;">
                      </div>

                      <div v-if="!seat.numero || seat.clase === 'empty'" style="width: 50px; height: 50px;"></div>
                      <button v-else type="button" class="btn btn-sm d-flex flex-column align-items-center justify-content-center shadow-none border"
                              :class="{
                                'btn-secondary text-white': getTicketForSeat(seat.numero)?.estado === 'confirmado',
                                'btn-warning text-dark': getTicketForSeat(seat.numero)?.estado === 'reservado',
                                'btn-success text-white': ticketForm.numero_asiento === seat.numero,
                                'bg-light text-dark': !activeTrip.asientos_ocupados.includes(seat.numero) && ticketForm.numero_asiento !== seat.numero
                              }"
                              @click="handleSeatClick(seat.numero)"
                              style="width: 50px; height: 50px; border-radius: 8px; transition: all 0.2s;">
                        <i class="fas fa-couch mb-1" :class="seat.clase === 'vip' ? 'text-primary' : ''" style="font-size: 1.2rem;"></i>
                        <span style="font-size: 0.75rem; font-weight: bold; line-height: 1;">{{ seat.numero }}</span>
                      </button>
                      
                    </template>
                  </div>
                </div>
              </div>

              <!-- Fallback layout for vehicles without layout_asientos -->
              <div v-else class="d-flex flex-wrap gap-2 justify-content-center mx-auto" style="max-width: 350px;">
                <template v-for="n in activeTrip.vehicle.capacidad_asientos" :key="n">
                  <button type="button" class="btn btn-sm d-flex flex-column align-items-center justify-content-center shadow-none border"
                          :class="{
                            'btn-secondary text-white': getTicketForSeat(n)?.estado === 'confirmado',
                            'btn-warning text-dark': getTicketForSeat(n)?.estado === 'reservado',
                            'btn-success text-white': ticketForm.numero_asiento === n,
                            'bg-light text-dark': !activeTrip.asientos_ocupados.includes(n) && ticketForm.numero_asiento !== n
                          }"
                          @click="handleSeatClick(n)"
                          style="width: 50px; height: 50px; border-radius: 8px;">
                    <i class="fas fa-couch mb-1" style="font-size: 1.2rem;"></i>
                    <span style="font-size: 0.75rem; font-weight: bold; line-height: 1;">{{ n }}</span>
                  </button>
                </template>
              </div>

              <div v-if="ticketForm.errors.numero_asiento" class="text-danger text-center small mt-3 fw-medium">
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
              <div class="col-md-6 mb-3">
                <label class="form-label">Estado de Pago</label>
                <select v-model="ticketForm.estado_pago" class="form-select" :class="{ 'is-invalid': ticketForm.errors.estado_pago }" :disabled="ticketForm.tipo_documento === 'BOLETA' || ticketForm.tipo_documento === 'FACTURA'">
                  <option value="pendiente">Pendiente (Pago en ruta / destino)</option>
                  <option value="pagado">Pagado (Anticipado)</option>
                </select>
                <div class="invalid-feedback">{{ ticketForm.errors.estado_pago }}</div>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Método de Pago</label>
                <select v-model="ticketForm.metodo_pago" class="form-select" :class="{ 'is-invalid': ticketForm.errors.metodo_pago }" :disabled="isPending">
                  <option value="pendiente" v-if="isPending">Por Definir</option>
                  <option value="efectivo">Efectivo</option>
                  <option value="yape">Yape</option>
                  <option value="plin">Plin</option>
                  <option value="transferencia">Transferencia</option>
                </select>
                <div class="invalid-feedback">{{ ticketForm.errors.metodo_pago }}</div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Tipo de Documento</label>
                <select v-model="ticketForm.tipo_documento" class="form-select" :class="{ 'is-invalid': ticketForm.errors.tipo_documento }" :disabled="isPending">
                  <option value="BOLETA" v-if="!isPending">Boleta Electrónica</option>
                  <option value="FACTURA" v-if="!isPending">Factura Electrónica</option>
                  <option value="TICKET_INTERNO">Ticket Interno (No SUNAT)</option>
                </select>
                <div class="invalid-feedback">{{ ticketForm.errors.tipo_documento }}</div>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Fecha Emisión</label>
                <input type="datetime-local" v-model="ticketForm.emitido_en" class="form-control" :class="{ 'is-invalid': ticketForm.errors.emitido_en }" required>
                <div class="invalid-feedback">{{ ticketForm.errors.emitido_en }}</div>
              </div>
            </div>

            <!-- Datos de Facturación (Opcional para Boleta, Obligatorio para Factura) -->
            <div class="bg-light p-3 rounded mb-3" v-if="ticketForm.tipo_documento === 'FACTURA' || ticketForm.tipo_documento === 'BOLETA'">
              <div class="form-check mb-2" v-if="ticketForm.tipo_documento === 'BOLETA'">
                <input class="form-check-input" type="checkbox" id="facturarTercero" v-model="ticketForm.facturar_tercero">
                <label class="form-check-label fw-bold" for="facturarTercero">
                  Emitir comprobante a nombre de otra persona (Tercero)
                </label>
              </div>
              
              <div class="row" v-if="ticketForm.tipo_documento === 'FACTURA' || ticketForm.facturar_tercero">
                <div class="col-md-4 mb-3">
                  <label class="form-label">{{ ticketForm.tipo_documento === 'FACTURA' ? 'RUC Cliente' : 'DNI/Doc Cliente' }}</label>
                  <input type="text" v-model="ticketForm.documento_facturacion" @blur="buscarClienteFacturacion" class="form-control" :class="{ 'is-invalid': ticketForm.errors.documento_facturacion }" :required="ticketForm.tipo_documento === 'FACTURA' || ticketForm.facturar_tercero">
                  <div class="invalid-feedback">{{ ticketForm.errors.documento_facturacion }}</div>
                </div>
                <div class="col-md-8 mb-3">
                  <label class="form-label">Razón Social / Nombre Completo</label>
                  <input type="text" v-model="ticketForm.nombre_facturacion" class="form-control" :class="{ 'is-invalid': ticketForm.errors.nombre_facturacion }" :required="ticketForm.tipo_documento === 'FACTURA' || ticketForm.facturar_tercero">
                  <div class="invalid-feedback">{{ ticketForm.errors.nombre_facturacion }}</div>
                </div>
              </div>
            </div>

          </div>
          <div class="modal-footer d-flex justify-content-between">
            <div>
              <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancelar</button>
              <button v-if="ticketForm.id" type="button" class="btn btn-outline-danger" @click="anularReserva" :disabled="ticketForm.processing">
                <i class="fas fa-trash-alt me-1"></i> Anular Reserva
              </button>
            </div>
            
            <button type="submit" class="btn" :class="submitBtnClass" :disabled="!isFormValid" @click.prevent="executeDynamicSubmit">
              <i class="fas" :class="isPending ? 'fa-bookmark' : 'fa-check-circle'"></i> {{ submitBtnText }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal Encomiendas -->
  <PackageFormModal ref="packageModal" :trips="trips" />

</template>
