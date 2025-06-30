<template>
  <div v-if="show" class="fixed top-4 right-4 z-50">
    <div
      :class="alertClasses"
      class="px-4 py-3 rounded shadow-lg flex items-center justify-between"
    >
      <div class="flex items-center">
        <svg
          v-if="type === 'success'"
          class="h-6 w-6 text-green-500 mr-2"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
        <svg
          v-if="type === 'error'"
          class="h-6 w-6 text-red-500 mr-2"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
        <span>{{ message }}</span>
      </div>
      <button @click="show = false" class="ml-4 text-gray-500 hover:text-gray-700">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M6 18L18 6M6 6l12 12"
          />
        </svg>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  type: {
    type: String,
    default: 'success',
    validator: (value) => ['success', 'error'].includes(value),
  },
  message: {
    type: String,
    required: true,
  },
})

const show = ref(true)

const alertClasses = computed(() => {
  return {
    'bg-green-100 text-green-700': props.type === 'success',
    'bg-red-100 text-red-700': props.type === 'error',
  }
})
</script>
