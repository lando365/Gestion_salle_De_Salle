<template>
  <div class="min-h-screen bg-gray-50">
    <Navbar />
    <Sidebar />

    <main class="pt-16 pl-64">
      <div class="px-6 py-6">
        <router-view />
      </div>
    </main>
  </div>
</template>

<script setup>
import Navbar from './Navbar.vue'
import Sidebar from './Sidebar.vue'
</script>
