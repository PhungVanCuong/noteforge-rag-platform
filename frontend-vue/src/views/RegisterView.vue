<template>
  <div class="min-h-screen blueprint-grid-bg flex items-center justify-center p-4">
    <div class="blueprint-card max-w-md w-full p-8 shadow-2xl">
      <!-- Header -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-blueprint text-blueprint-accent text-3xl shadow-lg mb-3">
          📝
        </div>
        <h1 class="text-2xl font-bold text-blueprint-dark">Đăng Ký Tài Khoản</h1>
        <p class="text-xs text-gray-600 mt-1">Hệ thống sẽ gửi mã xác thực OTP 6 số đến email của bạn</p>
      </div>

      <!-- Error Alert -->
      <div v-if="errorMessage" class="mb-5 p-3 rounded-lg bg-red-100 border border-red-300 text-red-700 text-xs font-medium">
        {{ errorMessage }}
      </div>

      <!-- Register Form -->
      <form @submit.prevent="handleRegister" class="space-y-4">
        <div>
          <label class="block text-xs font-bold text-blueprint-dark mb-1">Họ và tên</label>
          <input
            v-model="name"
            type="text"
            required
            placeholder="Nguyễn Văn A"
            class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 bg-white text-sm focus:ring-2 focus:ring-blueprint focus:border-blueprint outline-none"
          />
        </div>

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
          <label class="block text-xs font-bold text-blueprint-dark mb-1">Mật khẩu (tối thiểu 6 ký tự)</label>
          <input
            v-model="password"
            type="password"
            required
            minlength="6"
            placeholder="••••••••"
            class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 bg-white text-sm focus:ring-2 focus:ring-blueprint focus:border-blueprint outline-none"
          />
        </div>

        <div>
          <label class="block text-xs font-bold text-blueprint-dark mb-1">Xác nhận mật khẩu</label>
          <input
            v-model="passwordConfirm"
            type="password"
            required
            placeholder="••••••••"
            class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 bg-white text-sm focus:ring-2 focus:ring-blueprint focus:border-blueprint outline-none"
          />
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full py-3 rounded-lg bg-blueprint hover:bg-blueprint-light text-white font-bold text-sm shadow-md transition-all disabled:opacity-50 mt-2"
        >
          {{ loading ? 'Đang gửi mã OTP...' : 'Gửi mã OTP xác thực' }}
        </button>
      </form>

      <!-- Footer Link -->
      <div class="mt-6 text-center text-xs text-gray-600">
        Đã có tài khoản?
        <router-link to="/login" class="font-bold text-blueprint hover:underline ml-1">
          Đăng nhập ngay
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const router = useRouter()

const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirm = ref('')
const loading = ref(false)
const errorMessage = ref('')

const handleRegister = async () => {
  if (password.value !== passwordConfirm.value) {
    errorMessage.value = 'Mật khẩu xác nhận không khớp.'
    return
  }

  loading.value = true
  errorMessage.value = ''

  try {
    await authStore.register(name.value, email.value, password.value)
    router.push('/verify-otp')
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Đăng ký tài khoản thất bại.'
  } finally {
    loading.value = false
  }
}
</script>
