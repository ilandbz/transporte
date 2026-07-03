<script setup lang="ts">
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const flash = computed(() => usePage().props.flash as { success?: string; error?: string } | null)
</script>

<template>
  <div v-if="flash?.success" class="bg-green-50 border border-green-200 rounded-lg p-3 flex items-center gap-2 text-green-800 text-sm mb-4">
    ✅ {{ flash.success }}
  </div>
  <div v-if="flash?.error" class="bg-red-50 border border-red-200 rounded-lg p-3 flex items-center gap-2 text-red-800 text-sm mb-4">
    ❌ {{ flash.error }}
  </div>
</template>
