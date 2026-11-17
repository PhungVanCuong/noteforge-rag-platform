<template>
  <div class="min-h-screen blueprint-grid-bg flex items-center justify-center p-4">
    <div class="blueprint-card max-w-sm w-full p-8 text-center shadow-2xl">
      <div class="w-12 h-12 border-4 border-blueprint-accent border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
      <h2 class="text-base font-bold text-blueprint-dark mb-1">Đang xác thực Google OAuth...</h2>
      <p class="text-xs text-gray-500 font-mono">Đang đồng bộ phiên đăng nhập vào hệ thống</p>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

onMounted(async () => {
  const urlParams = new URLSearchParams(window.location.search)
  const token = urlParams.get('token')

  if (token) {
    localStorage.setItem('noteforge_token', token)
    authStore.token = token
    try {
      await authStore.fetchUser()
      router.push('/dashboard')
    } catch (err) {
      router.push('/login?error=' + encodeURIComponent('Không thể tải thông tin tài khoản Google.'))
    }
  } else {
    router.push('/login?error=' + encodeURIComponent('Đăng nhập Google thất bại hoặc bị hủy.'))
  }
})
</script>
