import { defineStore } from 'pinia'
import { useApi } from '@/composables/useApi'

export const usePaymentStore = defineStore('payments', {
  state: () => ({
    payments: [],
    loading: false,
    error: null,
  }),
  actions: {
    async fetchPayments() {
      this.loading = true
      this.error = null
      try {
        const { get } = useApi()
        const response = await get('/payments')
        this.payments = response.data.map((p) => ({
          ...p,
          payment_date: p.payment_date.split(' ')[0],
        }))
      } catch (error) {
        this.error = error.message
        console.error('Payment fetch error:', error)
      } finally {
        this.loading = false
      }
    },
  },
})
