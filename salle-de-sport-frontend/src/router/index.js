import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/LoginView.vue'),
    meta: { guestOnly: true },
  },
  {
    path: '/',
    component: () => import('@/components/common/AppLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'dashboard',
        component: () => import('@/views/DashboardView.vue'),
      },
      {
        path: '/members',
        name: 'members',
        component: () => import('@/views/MembersView.vue'),
      },
      {
        path: '/reservations',
        name: 'reservations',
        component: () => import('@/views/ReservationsView.vue'),
      },
      {
        path: '/services',
        name: 'services',
        component: () => import('@/views/ServicesView.vue'),
      },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()

  if (!authStore.isInitialized) {
    await authStore.fetchUser()
  }

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return next('/login')
  }

  if (to.meta.guestOnly && authStore.isAuthenticated) {
    return next('/')
  }

  return next()
})

export default router
