<template>
  <aside
    class="fixed top-0 left-0 bottom-0 w-64 bg-white border-r border-gray-200 pt-16 z-10 transition-all duration-300"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
  >
    <div class="px-4 py-6">
      <nav class="space-y-1">
        <router-link
          to="/"
          class="flex items-center px-4 py-2 text-sm font-medium rounded-md hover:bg-blue-50 hover:text-blue-600"
          active-class="bg-blue-50 text-blue-600"
        >
          <svg class="mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
            />
          </svg>
          Dashboard
        </router-link>

        <router-link
          to="/members"
          class="flex items-center px-4 py-2 text-sm font-medium rounded-md hover:bg-blue-50 hover:text-blue-600"
          active-class="bg-blue-50 text-blue-600"
        >
          <svg class="mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
            />
          </svg>
          Membres
        </router-link>

        <router-link
          to="/reservations"
          class="flex items-center px-4 py-2 text-sm font-medium rounded-md hover:bg-blue-50 hover:text-blue-600"
          active-class="bg-blue-50 text-blue-600"
        >
          <svg class="mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
            />
          </svg>
          Réservations
        </router-link>

        <router-link
          to="/services"
          class="flex items-center px-4 py-2 text-sm font-medium rounded-md hover:bg-blue-50 hover:text-blue-600"
          active-class="bg-blue-50 text-blue-600"
        >
          <svg class="mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"
            />
          </svg>
          Services
        </router-link>
      </nav>
    </div>
  </aside>
</template>

<script setup>
import { ref } from 'vue'

const sidebarOpen = ref(true)
</script>
