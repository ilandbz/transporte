<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3'
import { onMounted } from 'vue'

interface Props {
  title?: string
}
defineProps<Props>()

const page = usePage()
// auth.user viene del HandleInertiaRequests middleware
const user = (page.props.auth as any)?.user ?? null
const activeBranch = (page.props.auth as any)?.active_branch ?? null
const allBranches = (page.props.auth as any)?.all_branches ?? []

function isActive(path: string): boolean {
  return page.url.startsWith(path)
}

function switchBranch(branchId: number) {
  import('@inertiajs/vue3').then(({ router }) => {
    router.post(`/branches/switch/${branchId}`, {}, {
      preserveScroll: true,
      onSuccess: () => {
        window.location.reload()
      }
    })
  })
}

onMounted(() => {
  // Fix para AnchorJS si no está disponible
  if (typeof (window as any).AnchorJS === 'undefined') {
    (window as any).AnchorJS = function() {
      return { add: () => {}, remove: () => {} }
    }
  }
})
</script>

<template>
  <main class="main" id="top">
    <div class="container" data-layout="container">
  
      <!-- NAVBAR VERTICAL (sidebar) -->
      <nav class="navbar navbar-light navbar-vertical navbar-expand-xl">
        <div class="d-flex align-items-center">
          <div class="toggle-icon-wrapper">
            <button class="btn navbar-toggler-humburger-icon navbar-vertical-toggle"
              data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Toggle Navigation">
              <span class="navbar-toggle-icon"><span class="toggle-line"></span></span>
            </button>
          </div>
          <Link class="navbar-brand" href="/dashboard">
            <div class="d-flex align-items-center py-3">
              <div class="avatar avatar-xl me-2 bg-success-subtle rounded-circle d-flex align-items-center justify-content-center">
                <i class="fas fa-bus text-success" data-allow-mismatch></i>
              </div>
              <div>
                <span class="font-sans-serif fw-bold">Shinhua</span>
                <p class="mb-0 fs--2 text-500">Transportes</p>
              </div>
            </div>
          </Link>
        </div>
        <div class="collapse navbar-collapse" id="navbarVerticalCollapse">
          <div class="navbar-vertical-content scrollbar">
            <!-- items de navegación aquí -->
            <ul class="navbar-nav flex-column mb-3" id="navbarVerticalNav">

              <!-- Dashboard -->
              <li class="nav-item">
                <Link class="nav-link" :class="{ active: isActive('/dashboard') }" href="/dashboard" role="button">
                  <div class="d-flex align-items-center">
                    <span class="nav-link-icon"><span class="fas fa-tachometer-alt" data-allow-mismatch></span></span>
                    <span class="nav-link-text ps-1">Dashboard</span>
                  </div>
                </Link>
              </li>

              <!-- Sección con label: OPERACIONES -->
              <li class="nav-item">
                <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
                  <div class="col-auto navbar-vertical-label">OPERACIONES</div>
                  <div class="col ps-0"><hr class="mb-0 navbar-vertical-divider"></div>
                </div>

                <Link class="nav-link" :class="{ active: isActive('/trips') }" href="/trips" role="button">
                  <div class="d-flex align-items-center">
                    <span class="nav-link-icon"><span class="fas fa-route" data-allow-mismatch></span></span>
                    <span class="nav-link-text ps-1">Viajes</span>
                  </div>
                </Link>

                <Link class="nav-link" :class="{ active: isActive('/tickets') }" href="/tickets" role="button">
                  <div class="d-flex align-items-center">
                    <span class="nav-link-icon"><span class="fas fa-ticket-alt" data-allow-mismatch></span></span>
                    <span class="nav-link-text ps-1">Tickets</span>
                  </div>
                </Link>

                <Link class="nav-link" :class="{ active: isActive('/packages') }" href="/packages" role="button">
                  <div class="d-flex align-items-center">
                    <span class="nav-link-icon"><span class="fas fa-box" data-allow-mismatch></span></span>
                    <span class="nav-link-text ps-1">Encomiendas</span>
                  </div>
                </Link>
              </li>

              <!-- Sección con label: FACTURACIÓN -->
              <li class="nav-item">
                <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
                  <div class="col-auto navbar-vertical-label">FACTURACIÓN</div>
                  <div class="col ps-0"><hr class="mb-0 navbar-vertical-divider"></div>
                </div>

                <Link class="nav-link" :class="{ active: isActive('/billing/cpe') }" href="/billing/cpe" role="button">
                  <div class="d-flex align-items-center">
                    <span class="nav-link-icon"><span class="fas fa-file-invoice" data-allow-mismatch></span></span>
                    <span class="nav-link-text ps-1">Consola CPE</span>
                  </div>
                </Link>

                <Link class="nav-link" :class="{ active: isActive('/billing/sync') }" href="/billing/sync" role="button">
                  <div class="d-flex align-items-center">
                    <span class="nav-link-icon"><span class="fas fa-sync-alt" data-allow-mismatch></span></span>
                    <span class="nav-link-text ps-1">Sync Pendientes</span>
                  </div>
                </Link>
              </li>

              <!-- Sección con label: REPORTES -->
              <li class="nav-item">
                <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
                  <div class="col-auto navbar-vertical-label">REPORTES</div>
                  <div class="col ps-0"><hr class="mb-0 navbar-vertical-divider"></div>
                </div>

                <Link class="nav-link" :class="{ active: isActive('/reports/caja') }" href="/reports/caja" role="button">
                  <div class="d-flex align-items-center">
                    <span class="nav-link-icon"><span class="fas fa-cash-register" data-allow-mismatch></span></span>
                    <span class="nav-link-text ps-1">Liquidación de Caja</span>
                  </div>
                </Link>
              </li>

              <!-- Sección con label: CONFIGURACIÓN -->
              <li class="nav-item">
                <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
                  <div class="col-auto navbar-vertical-label">CONFIGURACIÓN</div>
                  <div class="col ps-0"><hr class="mb-0 navbar-vertical-divider"></div>
                </div>

                <Link class="nav-link" :class="{ active: isActive('/settings/branches') }" href="/settings/branches" role="button">
                  <div class="d-flex align-items-center">
                    <span class="nav-link-icon"><span class="fas fa-building" data-allow-mismatch></span></span>
                    <span class="nav-link-text ps-1">Sucursales</span>
                  </div>
                </Link>

                <Link class="nav-link" :class="{ active: isActive('/settings/users') }" href="/settings/users" role="button">
                  <div class="d-flex align-items-center">
                    <span class="nav-link-icon"><span class="fas fa-users" data-allow-mismatch></span></span>
                    <span class="nav-link-text ps-1">Usuarios</span>
                  </div>
                </Link>

                <Link class="nav-link" :class="{ active: isActive('/settings/vehicles') }" href="/settings/vehicles" role="button">
                  <div class="d-flex align-items-center">
                    <span class="nav-link-icon"><span class="fas fa-truck" data-allow-mismatch></span></span>
                    <span class="nav-link-text ps-1">Vehículos</span>
                  </div>
                </Link>

                <Link class="nav-link" :class="{ active: isActive('/settings/routes') }" href="/settings/routes" role="button">
                  <div class="d-flex align-items-center">
                    <span class="nav-link-icon"><span class="fas fa-map-marked-alt" data-allow-mismatch></span></span>
                    <span class="nav-link-text ps-1">Rutas</span>
                  </div>
                </Link>
              </li>

              <li class="nav-item">
                <Link class="nav-link" :class="{ 'active': $page.url.startsWith('/clientes') }" href="/clientes">
                  <div class="d-flex align-items-center">
                    <span class="nav-link-icon"><i class="fas fa-users"></i></span>
                    <span class="nav-link-text ps-1">Clientes</span>
                  </div>
                </Link>
              </li>

            </ul>
          </div>
        </div>
      </nav>
  
      <!-- ÁREA PRINCIPAL -->
      <div class="content">
  
        <!-- TOP NAVBAR -->
        <nav class="navbar navbar-light navbar-glass navbar-top navbar-expand">
          <button class="btn navbar-toggler-humburger-icon navbar-toggler me-1 me-sm-3"
            type="button" data-bs-toggle="collapse"
            data-bs-target="#navbarVerticalCollapse">
            <span class="navbar-toggle-icon"><span class="toggle-line"></span></span>
          </button>
          
          <ul class="navbar-nav navbar-nav-icons ms-auto flex-row align-items-center gap-2">
            
            <li class="nav-item dropdown px-2" v-if="user?.role === 'admin' && allBranches.length > 0">
              <a class="nav-link dropdown-toggle px-0" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-building me-1"></i> {{ activeBranch?.name || 'Seleccionar Sucursal' }}
              </a>
              <div class="dropdown-menu dropdown-caret dropdown-menu-end py-0">
                <div class="bg-white py-2 rounded-2">
                  <a class="dropdown-item" href="#" v-for="branch in allBranches" :key="branch.id" @click.prevent="switchBranch(branch.id)">
                    <i class="fas fa-check text-success me-2" v-if="activeBranch?.id === branch.id"></i>
                    <span :class="{'ps-4': activeBranch?.id !== branch.id}">{{ branch.name }}</span>
                  </a>
                </div>
              </div>
            </li>
            
            <li class="nav-item px-2" v-else-if="activeBranch">
              <span class="badge badge-subtle-primary"><i class="fas fa-building me-1"></i> {{ activeBranch.name }}</span>
            </li>
            
            <li class="nav-item">
              <span class="text-700 fw-semi-bold">{{ user?.name }}</span>
              <span class="badge bg-success ms-2">{{ user?.role }}</span>
            </li>
            <li class="nav-item">
              <Link href="/logout" method="post" as="button" class="btn btn-sm btn-falcon-default">
                <i class="fas fa-sign-out-alt me-1" data-allow-mismatch></i> Salir
              </Link>
            </li>
          </ul>
        </nav>
  
        <!-- CONTENIDO DE LA PÁGINA -->
        <div class="slot-content">
          <slot></slot>
        </div>
  
        <!-- FOOTER -->
        <footer class="footer">
          <div class="row g-0 justify-content-between fs--1 mt-4 mb-3">
            <div class="col-12 col-sm-auto text-center">
              <p class="mb-0 text-600">Shinhua Transportes &copy; 2026</p>
            </div>
          </div>
        </footer>
  
      </div><!-- /.content -->
    </div><!-- /.container -->
  </main>
</template>
