<template>
  <div class="min-h-screen blueprint-grid-bg flex items-center justify-center p-4">
    <div class="blueprint-card max-w-md w-full p-8 shadow-2xl">
      <!-- Header -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-blueprint text-blueprint-accent text-3xl shadow-lg mb-3">
          ⚡
        </div>
        <h1 class="text-2xl font-bold text-blueprint-dark">Đăng Nhập NoteForge AI</h1>
        <p class="text-xs text-gray-600 mt-1">Nền tảng Trợ lý RAG & Chuyển hóa Học liệu Thông minh</p>
      </div>

      <!-- Error Alert -->
      <div v-if="errorMessage" class="mb-5 p-3 rounded-lg bg-red-100 border border-red-300 text-red-700 text-xs font-medium">
        {{ errorMessage }}
      </div>

      <!-- Login Form -->
      <form @submit.prevent="handleLogin" class="space-y-4">
        <div>
          <label class="block text-xs font-bold text-blueprint-dark mb-1">Email học tập</label>
          <input
            v-model="email"
            type="email"
            required
            placeholder="student@noteforge.edu.vn"
            class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 bg-white text-sm focus:ring-2 focus:ring-blueprint focus:border-blueprint outline-none"
          />
        </div>

        <div>
          <label class="block text-xs font-bold text-blueprint-dark mb-1">Mật khẩu</label>
          <input
            v-model="password"
            type="password"
            required
            placeholder="••••••••"
            class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 bg-white text-sm focus:ring-2 focus:ring-blueprint focus:border-blueprint outline-none"
          />
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full py-3 rounded-lg bg-blueprint hover:bg-blueprint-light text-white font-bold text-sm shadow-md transition-all disabled:opacity-50"
        >
          {{ loading ? 'Đang xác thực...' : 'Đăng nhập vào hệ thống' }}
        </button>
      </form>

      <!-- Divider -->
      <div class="relative my-6 text-center">
        <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-300"></div></div>
        <span class="relative px-3 bg-blueprint-paper text-xs text-gray-500 font-mono">HOẶC TIẾP TỤC VỚI</span>
      </div>

      <!-- Google OAuth Button -->
      <button
        @click="loginWithGoogle"
        type="button"
        class="w-full py-2.5 px-4 rounded-lg bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 font-semibold text-xs flex items-center justify-center gap-2.5 shadow-sm transition-colors"
      >
        <svg class="w-4 h-4" viewBox="0 0 24 24">
          <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
          <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
          <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
          <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
        </svg>
        Đăng nhập với Google OAuth 2.0
      </button>

      <!-- Footer Link -->
      <div class="mt-6 text-center text-xs text-gray-600">
        Chưa có tài khoản?
        <router-link to="/register" class="font-bold text-blueprint hover:underline ml-1">
          Đăng ký & nhận mã OTP
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import apiClient from '@/api/axios'

const authStore = useAuthStore()
const router = useRouter()
const route = useRoute()

const email = ref('')
const password = ref('')
const loading = ref(false)
const errorMessage = ref('')

onMounted(() => {
  if (route.query.error) {
    errorMessage.value = decodeURIComponent(route.query.error)
  }
})

const handleLogin = async () => {
  loading.value = true
  errorMessage.value = ''
  try {
    await authStore.login(email.value, password.value)
    router.push('/dashboard')
  } catch (err) {
    if (err.response?.data?.needs_verification) {
      router.push('/verify-otp')
    } else {
      errorMessage.value = err.response?.data?.message || 'Đăng nhập không thành công.'
    }
  } finally {
    loading.value = false
  }
}

const loginWithGoogle = async () => {
  try {
    const response = await apiClient.get('/auth/google/redirect')
    if (response.data.url) {
      window.location.href = response.data.url
    }
  } catch (err) {
    errorMessage.value = 'Không thể chuyển hướng đến Google OAuth.'
  }
}
</script>
