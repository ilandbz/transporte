<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import StatusBadge from '@/Components/StatusBadge.vue'
import ConvertCpeModal from '@/Components/ConvertCpeModal.vue'
import { ref } from 'vue'

declare const Swal: any

interface Props {
  tickets: {
    data: any[]
    links: any[]
    current_page: number
  }
  filtros: {
    fecha?: string
    tipo_documento?: string
    sincronizado?: string
    placa?: string
  }
  stats: {
    total_hoy: number
    sin_sincronizar: number
    aceptados_hoy: number
  }
}

const props = defineProps<Props>()
const convertModalRef = ref<InstanceType<typeof ConvertCpeModal> | null>(null)

const filter = (key: string, value: string) => {
  const params = { ...props.filtros, [key]: value }
  router.get('/tickets', params, { preserveState: true })
}

const deleteTicket = async (ticket: any) => {
  const result = await Swal.fire({
    title: '¿Eliminar ticket?',
    text: 'Esta acción borrará el ticket y liberará el asiento.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#e63757',
    cancelButtonColor: '#748194',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
  })

  if (result.isConfirmed) {
    router.delete(`/tickets/${ticket.id}`)
  }
}

function togglePayment(ticket: any) {
  router.patch(`/tickets/${ticket.id}/toggle-payment`, {}, {
    preserveScroll: true
  })
}

const formatDate = (dateStr: string) => {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  const pad = (n: number) => n.toString().padStart(2, '0')
  return `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`
}

const anularTicket = async (ticket: any) => {
  if (ticket.estado === 'anulado') return;
  
  const result = await Swal.fire({
    title: 'Anular Comprobante',
    text: 'Ingrese el motivo de la anulación (ej. Error en digitación)',
    input: 'text',
    inputAttributes: {
      autocapitalize: 'off',
      minlength: '3',
      maxlength: '100'
    },
    showCancelButton: true,
    confirmButtonText: 'Sí, Anular',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#e63757',
    showLoaderOnConfirm: true,
    preConfirm: (motivo: string) => {
      if (!motivo || motivo.length < 3) {
        Swal.showValidationMessage('El motivo debe tener al menos 3 caracteres')
        return false
      }
      return new Promise((resolve) => {
        router.post(`/tickets/${ticket.id}/anular`, { motivo }, {
          preserveScroll: true,
          onSuccess: (page) => {
            // @ts-ignore
            if (page.props.flash?.error) {
              // @ts-ignore
              Swal.showValidationMessage(page.props.flash.error)
              resolve(false)
            } else {
              resolve(true)
            }
          },
          onError: (errors) => {
            Swal.showValidationMessage(errors.motivo || 'Error al anular')
            resolve(false)
          }
        })
      })
    },
    allowOutsideClick: () => !Swal.isLoading()
  })

  if (result.isConfirmed && result.value) {
    Swal.fire({
      title: '¡Anulado!',
      text: 'El comprobante ha sido anulado correctamente.',
      icon: 'success'
    })
  }
}
</script>

<template>
  <AppLayout>
    <Head title="Tickets" />

    <!-- Stats Cards -->
    <div class="row g-3 mb-3">
      <div class="col-md-4">
        <div class="card h-100 border-start border-4 border-primary shadow-none">
          <div class="card-body">
            <h6 class="text-secondary text-uppercase fs--1 mb-1">Total Hoy</h6>
            <div class="fs-4 fw-bold text-dark">{{ stats.total_hoy }}</div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card h-100 border-start border-4 border-warning shadow-none">
          <div class="card-body">
            <h6 class="text-secondary text-uppercase fs--1 mb-1">Sin Sincronizar (Global)</h6>
            <div class="fs-4 fw-bold text-dark">{{ stats.sin_sincronizar }}</div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card h-100 border-start border-4 border-success shadow-none">
          <div class="card-body">
            <h6 class="text-secondary text-uppercase fs--1 mb-1">Aceptados Hoy</h6>
            <div class="fs-4 fw-bold text-dark">{{ stats.aceptados_hoy }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters & Table -->
    <div class="card">
      <div class="card-header bg-light d-flex flex-wrap align-items-center gap-3">
        <!-- Agregar selectores de filtros rápidos aquí -->
        <div>
          <label class="form-label fs--2 text-uppercase mb-1">Sincronizado</label>
          <select @change="(e) => filter('sincronizado', (e.target as HTMLSelectElement).value)" class="form-select form-select-sm">
            <option value="">Todos</option>
            <option value="true" :selected="filtros.sincronizado === 'true'">Sí</option>
            <option value="false" :selected="filtros.sincronizado === 'false'">No</option>
          </select>
        </div>
      </div>

      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-secondary text-uppercase fs--2 font-sans-serif">
              <tr>
                <th class="ps-3 py-3">N° CPE</th>
                <th class="py-3">Pasajero / DNI</th>
                <th class="py-3">Tramo</th>
                <th class="text-center py-3">Asiento</th>
                <th class="text-center py-3">Placa</th>
                <th class="text-center py-3">Clase</th>
                <th class="text-end py-3">Monto</th>
                <th class="text-center py-3">Estado SUNAT</th>
                <th class="py-3">Mensaje SUNAT</th>
                <th class="py-3">Fecha</th>
                <th class="text-center py-3 pe-3">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="t in tickets.data" :key="t.id">
                <td class="ps-3">
                  <span class="fw-bold text-dark">{{ t.tipo_documento }}</span><br>
                  <span class="text-muted fs--1">{{ t.serie_cpe ? `${t.serie_cpe}-${t.correlativo_cpe}` : 'PENDIENTE' }}</span>
                  <span v-if="t.emitido_en_contingencia" title="Contingencia" class="ms-1">📵</span>
                </td>
                <td>
                  <div class="fw-medium text-dark">{{ t.cliente_nombre }}</div>
                  <div class="text-muted fs--2">{{ t.cliente_doc }}</div>
                </td>
                <td class="fs--1 fw-medium text-secondary">
                  {{ t.origen_tramo }} <i class="fas fa-arrow-right mx-1 text-400"></i> {{ t.destino_tramo }}
                </td>
                <td class="text-center fw-bold text-dark">
                  {{ t.numero_asiento }}
                </td>
                <td class="text-center">
                  <span class="badge border border-secondary text-secondary fw-medium">
                    {{ t.trip?.vehicle?.placa || t.placa_vehiculo }}
                  </span>
                </td>
                <td class="text-center">
                  <span class="badge border border-secondary text-secondary fw-medium">
                    {{ t.clase }}
                  </span>
                </td>
                <td class="text-end fw-semibold text-success">
                  S/ {{ parseFloat(t.precio).toFixed(2) }}
                  <br>
                  <span 
                    @click="togglePayment(t)"
                    class="badge rounded-pill mt-1" 
                    :class="t.estado_pago === 'pagado' ? 'bg-success' : 'bg-danger'"
                    :title="t.estado_pago === 'pagado' ? 'Marcar como pendiente' : 'Marcar como pagado'"
                    style="cursor: pointer; font-size: 0.65rem;"
                  >
                    {{ t.estado_pago === 'pagado' ? 'CANCELADO' : 'PENDIENTE' }}
                  </span>
                </td>
                <td class="text-center">
                  <span v-if="t.estado === 'anulado'" class="badge bg-danger">ANULADO</span>
                  <span v-else-if="t.tipo_documento === 'TICKET_INTERNO'" class="badge bg-secondary">INTERNO</span>
                  <span v-else-if="t.cdr_status === '0'" class="badge bg-success">ACEPTADO</span>
                  <span v-else-if="t.cdr_status === 'anulado'" class="badge bg-danger">BAJA</span>
                  <span v-else-if="t.cdr_status" class="badge bg-danger">RECHAZADO</span>
                  <span v-else-if="t.sincronizado" class="badge bg-success">SINC.</span>
                  <span v-else class="badge bg-warning">PENDIENTE</span>
                </td>
                <td class="fs--2 text-muted" style="max-width: 200px; white-space: normal;">
                  {{ t.cdr_descripcion || (t.tipo_documento === 'TICKET_INTERNO' ? 'Uso interno' : (t.sincronizado ? 'Enviado correctamente' : 'Pendiente de envío')) }}
                </td>
                <td class="fs--1 text-secondary">
                  {{ formatDate(t.emitido_en) }}
                </td>
                <td class="text-end pe-3">
                  <div class="d-inline-flex gap-2 align-items-center justify-content-end">
                    <div class="dropdown font-sans-serif position-static">
                      <button class="btn btn-link text-600 btn-sm dropdown-toggle btn-reveal" type="button" data-bs-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false" title="Imprimir Ticket">
                        <span class="fas fa-print fs--1"></span>
                      </button>
                      <div class="dropdown-menu dropdown-menu-end border py-0">
                        <div class="bg-white py-2">
                          <a class="dropdown-item" :href="`/tickets/${t.id}/print?format=80mm`" target="_blank">Imprimir (80mm)</a>
                          <a class="dropdown-item" :href="`/tickets/${t.id}/print?format=a4`" target="_blank">Formato A4/A5</a>
                        </div>
                      </div>
                    </div>
                    <button v-if="t.tipo_documento === 'TICKET_INTERNO'" @click="convertModalRef?.show(t)" class="btn btn-sm btn-link text-warning p-0 shadow-none" title="Convertir a Boleta/Factura">
                      <i class="fas fa-exchange-alt"></i>
                    </button>
                    
                    <button v-if="t.estado !== 'anulado' && (!t.trip || t.trip.estado === 'abierto')" class="btn btn-sm btn-link text-danger p-0 shadow-none ms-1" title="Anular Comprobante" @click="anularTicket(t)">
                      <i class="fas fa-ban"></i> Anular
                    </button>
                    <span v-else-if="t.estado !== 'anulado'" class="badge bg-secondary" title="El viaje ya partió o finalizó">Viajando</span>
                  </div>
                </td>
              </tr>
              <tr v-if="tickets.data.length === 0">
                <td colspan="10" class="text-center text-muted py-5">No hay tickets registrados.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>

  <!-- Modal para convertir TICKET_INTERNO a BOLETA/FACTURA -->
  <ConvertCpeModal ref="convertModalRef" @success="() => {
    Swal.fire('¡Éxito!', 'Comprobante generado correctamente', 'success')
  }" />
</template>
