<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <h2 class="text-2xl font-bold text-gray-900">Gestion des Services</h2>
      <button
        @click="showCreateModal = true"
        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
      >
        Nouveau Service
      </button>
    </div>

    <ServiceList :services="services" @edit="openEditModal" @delete="deleteService" />

    <!-- Modal -->
    <div v-if="showCreateModal || currentService" class="fixed inset-0 overflow-y-auto z-50">
      <div
        class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0"
      >
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
          <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true"
          >&#8203;</span
        >

        <div
          class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6"
        >
          <div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
              {{ currentService ? 'Modifier Service' : 'Nouveau Service' }}
            </h3>

            <ServiceForm :service="currentService" @submit="handleSubmit" @cancel="closeModal" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useServiceStore } from '@/stores/services'
import ServiceList from '@/components/ServiceList.vue'
import ServiceForm from '@/components/ServiceForm.vue'

const serviceStore = useServiceStore()

const showCreateModal = ref(false)
const currentService = ref(null)

const services = computed(() => serviceStore.services)

onMounted(async () => {
  await serviceStore.fetchServices()
})

const openEditModal = (service) => {
  currentService.value = { ...service }
}

const closeModal = () => {
  showCreateModal.value = false
  currentService.value = null
}

const handleSubmit = async (formData) => {
  try {
    if (currentService.value) {
      await serviceStore.updateService(currentService.value.id, formData)
    } else {
      await serviceStore.createService(formData)
    }
    closeModal()
  } catch (error) {
    console.error('Error saving service:', error)
  }
}

const deleteService = async (id) => {
  try {
    await serviceStore.deleteService(id)
  } catch (error) {
    console.error('Error deleting service:', error)
  }
}
</script>
