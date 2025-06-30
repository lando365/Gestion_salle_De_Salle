import { defineStore } from 'pinia'
import { useApi } from '@/composables/useApi'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    isAuthenticated: false,
    isInitialized: false,
  }),
  actions: {
    async login(credentials) {
      const { post } = useApi()
      try {
        const response = await post('/auth/login', credentials)
        localStorage.setItem('authToken', response.data.token)
        this.user = response.data.user
        this.isAuthenticated = true
        return response.data
      } catch (error) {
        throw error.response?.data || error
      }
    },
    async logout() {
      const { post } = useApi()
      try {
        await post('/auth/logout')
      } finally {
        this.clearAuth()
      }
    },
    clearAuth() {
      localStorage.removeItem('authToken')
      this.user = null
      this.isAuthenticated = false
    },
    async fetchUser() {
      const { get } = useApi()
      try {
        const response = await get('/user')
        this.user = response.data
        this.isAuthenticated = true
      } catch (error) {
        this.clearAuth()
      } finally {
        this.isInitialized = true
      }
    },
  },
})
