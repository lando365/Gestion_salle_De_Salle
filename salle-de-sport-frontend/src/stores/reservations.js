import { defineStore } from 'pinia'
import { useApi } from '@/composables/useApi'

export const useReservationStore = defineStore('reservations', {
  state: () => ({
    reservations: [],
    todayReservations: [],
    pagination: {},
    currentReservation: null,
  }),
  actions: {
    async fetchReservations(params = {}) {
      const { get } = useApi()
      const response = await get('/reservations', { params })
      this.reservations = response.data.data
      this.pagination = {
        currentPage: response.data.current_page,
        lastPage: response.data.last_page,
        total: response.data.total,
      }
    },

    async fetchTodayReservations() {
      const { get } = useApi()
      try {
        const response = await get('/reservations/today') // Utilisez un endpoint spécifique
        this.todayReservations = response.data
        return response.data
      } catch (error) {
        console.error('Error fetching today reservations:', error)
        return []
      }
    },

    async createReservation(reservationData) {
      const { post } = useApi()
      const response = await post('/reservations', reservationData)
      this.reservations.unshift(response.data)
      return response.data
    },
    async cancelReservation(id) {
      const { put } = useApi()
      const response = await put(`/reservations/${id}`, { status: 'cancelled' })
      const index = this.reservations.findIndex((r) => r.id === id)
      if (index !== -1) {
        this.reservations[index] = response.data
      }
      return response.data
    },
    async deleteReservation(id) {
      const { delete: destroy } = useApi()
      await destroy(`/reservations/${id}`)
      this.reservations = this.reservations.filter((r) => r.id !== id)
    },
  },
})
