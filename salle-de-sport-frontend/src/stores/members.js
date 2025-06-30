import { defineStore } from 'pinia'
import { useApi } from '@/composables/useApi'

export const useMemberStore = defineStore('members', {
  state: () => ({
    members: [],
    pagination: {},
    currentMember: null,
  }),
  actions: {
    async fetchMembers(params = {}) {
      const { get } = useApi()
      const response = await get('/members', { params })
      this.members = response.data.data
      this.pagination = {
        currentPage: response.data.current_page,
        lastPage: response.data.last_page,
        total: response.data.total,
      }
    },
    async fetchMember(id) {
      const { get } = useApi()
      const response = await get(`/members/${id}`)
      this.currentMember = response.data
    },
    async createMember(memberData) {
      const { post } = useApi()
      const response = await post('/members', memberData)
      this.members.unshift(response.data)
      return response.data
    },
    async updateMember(id, memberData) {
      const { put } = useApi()
      const response = await put(`/members/${id}`, memberData)
      const index = this.members.findIndex((m) => m.id === id)
      if (index !== -1) {
        this.members[index] = response.data
      }
      return response.data
    },
    async deleteMember(id) {
      const { delete: destroy } = useApi()
      await destroy(`/members/${id}`)
      this.members = this.members.filter((m) => m.id !== id)
    },
  },
})
