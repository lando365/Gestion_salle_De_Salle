<template>
  <div class="space-y-6">
    <h2 class="text-2xl font-bold text-gray-900">Tableau de bord</h2>

    <DashboardStats :stats="stats" />

    <div class="bg-white shadow rounded-lg overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Réservations aujourd'hui</h3>
        <button
          @click="refreshData"
          class="mt-2 inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-4 w-4 mr-1"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
            />
          </svg>
          Actualiser
        </button>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th
                scope="col"
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Membre
              </th>
              <th
                scope="col"
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Service
              </th>
              <th
                scope="col"
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Heure
              </th>
              <th
                scope="col"
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Statut
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="reservation in todayReservations" :key="reservation.id">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">
                  {{ reservation.member.first_name }} {{ reservation.member.last_name }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ reservation.service.name }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ reservation.start_time }} - {{ reservation.end_time }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="statusClasses(reservation.status)"
                  class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                >
                  {{ statusText(reservation.status) }}
                </span>
              </td>
            </tr>
            <tr v-if="todayReservations.length === 0">
              <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                Aucune réservation aujourd'hui
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useReservationStore } from '@/stores/reservations'
import { useMemberStore } from '@/stores/members'
import { usePaymentStore } from '@/stores/payments'
import DashboardStats from '@/components/DashboardStats.vue'

const reservationStore = useReservationStore()
const memberStore = useMemberStore()
const paymentStore = usePaymentStore()

const stats = ref({
  active_members: 0,
  today_reservations: 0,
  monthly_revenue: 0,
  expiring_soon: 0,
})

const todayReservations = computed(() => reservationStore.todayReservations)

async function loadStats() {
  // Charger toutes les données nécessaires
  await Promise.all([
    reservationStore.fetchTodayReservations(),
    memberStore.fetchMembers(),
    paymentStore.fetchPayments(),
  ])

  // Calculer les statistiques réelles
  const today = new Date().toISOString().split('T')[0]
  const currentMonth = new Date().getMonth() + 1
  const currentYear = new Date().getFullYear()

  stats.value = {
    active_members: memberStore.members.filter((m) => m.status === 'active').length,
    today_reservations: reservationStore.todayReservations.length,
    monthly_revenue: paymentStore.payments
      .filter((p) => {
        const paymentDate = new Date(p.payment_date)
        return (
          paymentDate.getMonth() + 1 === currentMonth && paymentDate.getFullYear() === currentYear
        )
      })
      .reduce((sum, payment) => sum + parseFloat(payment.amount), 0),
    expiring_soon: memberStore.members.filter((m) => {
      const endDate = new Date(m.subscription_end)
      const today = new Date()
      const diffTime = endDate - today
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
      return diffDays <= 7 && diffDays >= 0
    }).length,
  }
}

async function refreshData() {
  await loadStats()
}

onMounted(async () => {
  await loadStats()
})

function statusText(status) {
  const statuses = {
    confirmed: 'Confirmé',
    cancelled: 'Annulé',
    completed: 'Terminé',
  }
  return statuses[status] || status
}

function statusClasses(status) {
  return {
    'bg-green-100 text-green-800': status === 'confirmed',
    'bg-yellow-100 text-yellow-800': status === 'completed',
    'bg-red-100 text-red-800': status === 'cancelled',
  }
}
</script>
