<template>
  <router-view />
</template>

<script setup>
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'

const router = useRouter

const authStore = useAuthStore()

// Vérifier l'authentification au chargement de l'app
authStore.fetchUser().catch(() => {
  // Rediriger vers le login si non authentifié
  if (router.currentRoute.value.meta.requiresAuth) {
    router.push('/login')
  }
})
</script>
