<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <h2 class="text-2xl font-bold text-gray-900">Gestion des Réservations</h2>
      <button
        @click="showCreateModal = true"
        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
      >
        Nouvelle Réservation
      </button>
    </div>

    <ReservationCalendar :reservations="reservations" @date-selected="handleDateSelected" />

    <ReservationList
      :reservations="filteredReservations"
      @cancel="cancelReservation"
      @delete="deleteReservation"
    />

    <!-- Create/Edit Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 overflow-y-auto z-50">
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
          class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6"
        >
          <div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Nouvelle Réservation</h3>

            <ReservationForm
              :selected-date="selectedDate"
              @submit="createReservation"
              @cancel="showCreateModal = false"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useReservationStore } from '@/stores/reservations'
import { useMemberStore } from '@/stores/members'
import { useServiceStore } from '@/stores/services'
import ReservationCalendar from '@/components/ReservationCalendar.vue'
import ReservationList from '@/components/ReservationList.vue'
import ReservationForm from '@/components/ReservationForm.vue'

const reservationStore = useReservationStore()
const memberStore = useMemberStore()
const serviceStore = useServiceStore()

const showCreateModal = ref(false)
const selectedDate = ref(null)

const reservations = computed(() => reservationStore.reservations)
const filteredReservations = computed(() => {
  if (!selectedDate.value) return reservations.value
  return reservations.value.filter((r) => r.date === selectedDate.value)
})

onMounted(async () => {
  await reservationStore.fetchReservations()
  await memberStore.fetchMembers()
  await serviceStore.fetchServices()
})

const handleDateSelected = (date) => {
  selectedDate.value = date
}

const createReservation = async (reservationData) => {
  try {
    await reservationStore.createReservation(reservationData)
    showCreateModal.value = false
    selectedDate.value = reservationData.date
  } catch (error) {
    console.error('Error creating reservation:', error)
  }
}

const cancelReservation = async (id) => {
  try {
    await reservationStore.cancelReservation(id)
  } catch (error) {
    console.error('Error cancelling reservation:', error)
  }
}

const deleteReservation = async (id) => {
  try {
    await reservationStore.deleteReservation(id)
  } catch (error) {
    console.error('Error deleting reservation:', error)
  }
}
</script>
