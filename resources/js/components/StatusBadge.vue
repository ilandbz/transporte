<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{ status: string }>()

const map: Record<string, { cls: string; label: string }> = {
  aceptado:       { cls: 'bg-success', label: 'Aceptado' },
  rechazado:      { cls: 'bg-danger',  label: 'Rechazado' },
  pendiente:      { cls: 'bg-warning text-dark', label: 'Pendiente' },
  ticket_interno: { cls: 'bg-secondary', label: 'Ticket Interno' },
}

const item = computed(() => map[props.status] ?? { cls: 'bg-secondary', label: props.status })
</script>

<template>
  <span :class="['badge', item.cls]">{{ item.label }}</span>
</template>
