<script setup lang="ts">
interface Props {
  count: number
}
const props = defineProps<Props>()
</script>

<template>
  <div v-if="count > 0" class="bg-orange-50 border border-orange-200 rounded-lg p-4 flex items-center gap-3">
    <span class="text-orange-500 text-xl">⚠️</span>
    <p class="text-orange-800 text-sm font-medium">
      Hay <strong>{{ count }}</strong> ticket(s) pendientes de sincronizar desde los conductores.
    </p>
  </div>
</template>
