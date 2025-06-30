import { defineStore } from 'pinia'
import { useApi } from '@/composables/useApi'

export const useServiceStore = defineStore('services', {
  state: () => ({
    services: [],
    currentService: null,
  }),
  actions: {
    async fetchServices() {
      const { get } = useApi()
      const response = await get('/services')
      this.services = response.data
    },
    async createService(serviceData) {
      const { post } = useApi()
      const response = await post('/services', serviceData)
      this.services.push(response.data)
      return response.data
    },
    async updateService(id, serviceData) {
      const { put } = useApi()
      const response = await put(`/services/${id}`, serviceData)
      const index = this.services.findIndex((s) => s.id === id)
      if (index !== -1) {
        this.services[index] = response.data
      }
      return response.data
    },
    async deleteService(id) {
      const { delete: destroy } = useApi()
      await destroy(`/services/${id}`)
      this.services = this.services.filter((s) => s.id !== id)
    },
  },
})
