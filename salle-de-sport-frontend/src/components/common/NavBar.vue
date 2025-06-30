<template>
  <nav class="fixed top-0 left-0 right-0 bg-white border-b border-gray-200 z-10">
    <div class="px-6 py-3 flex justify-between items-center">
      <div class="flex items-center space-x-4">
        <button @click="sidebarOpen = !sidebarOpen" class="text-gray-500 focus:outline-none">
          <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 6h16M4 12h16M4 18h16"
            />
          </svg>
        </button>
      </div>

      <div class="flex items-center space-x-4">
        <div class="relative">
          <button
            @click="dropdownOpen = !dropdownOpen"
            class="flex items-center space-x-2 focus:outline-none"
          >
            <span class="text-gray-700">{{ authStore.user?.name }}</span>
            <svg
              class="h-4 w-4 text-gray-500"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M19 9l-7 7-7-7"
              />
            </svg>
          </button>

          <div
            v-show="dropdownOpen"
            class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-20"
          >
            <button
              @click="logout"
              class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
            >
              Déconnexion
            </button>
          </div>
        </div>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'

const authStore = useAuthStore()
const router = useRouter()
const dropdownOpen = ref(false)
const sidebarOpen = ref(true)

const logout = async () => {
  await authStore.logout()
  router.push('/login')
}
</script>
