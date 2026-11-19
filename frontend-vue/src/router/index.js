import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  {
    path: '/',
    redirect: '/dashboard',
  },
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/LoginView.vue'),
    meta: { guestOnly: true },
  },
  {
    path: '/register',
    name: 'register',
    component: () => import('@/views/RegisterView.vue'),
    meta: { guestOnly: true },
  },
  {
    path: '/verify-otp',
    name: 'verify-otp',
    component: () => import('@/views/VerifyOtpView.vue'),
    meta: { guestOnly: true },
  },
  {
    path: '/auth/callback',
    name: 'auth-callback',
    component: () => import('@/views/AuthCallback.vue'),
  },
  {
    path: '/dashboard',
    name: 'dashboard',
    component: () => import('@/views/DashboardView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/workspace/:id',
    name: 'workspace',
    component: () => import('@/views/WorkspaceView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/converter',
    name: 'converter',
    component: () => import('@/views/ConverterView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/study-sets',
    name: 'study-sets',
    component: () => import('@/views/StudyListView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/study-player/:id',
    name: 'study-player',
    component: () => import('@/views/StudyPlayerView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/tts-reader',
    name: 'tts-reader',
    component: () => import('@/views/TtsReaderView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/profile',
    name: 'profile',
    component: () => import('@/views/ProfileView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/study/:slug',
    name: 'shared-study',
    component: () => import('@/views/SharedStudyView.vue'),
  },
  {
    path: '/settings',
    name: 'settings',
    component: () => import('@/views/SettingsView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/dashboard',
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('noteforge_token')

  if (to.meta.requiresAuth && !token) {
    next({ name: 'login' })
  } else if (to.meta.guestOnly && token) {
    next({ name: 'dashboard' })
  } else {
    next()
  }
})

export default router
